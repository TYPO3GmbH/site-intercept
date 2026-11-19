#!/usr/bin/env python3
"""
Verifies config/nginx/redirects.conf without hand-written expectations.

1. Lint:      static checks of every redirect (capture groups, double slashes, permalink versions, ...)
2. Rules:     nginx -t, then a sample URL is generated for every rule from its pattern. Every rule must be
              reached by one of its samples (otherwise an earlier rule shadows it) and nginx must answer the
              sample exactly as the rule says.
3. Diff:      with --base-ref, the redirects of the base version and the current version are compared for
              the generated sample URLs of both versions. Changes are listed for review.
4. Live:      with --live-check, the destinations of changed redirects are requested on docs.typo3.org,
              a 404 is reported as warning.

Lint and rule errors fail the check, diff and live check are informational.

Usage:
    tests/Redirects/check.py                                     lint + rules
    tests/Redirects/check.py --base-ref origin/develop           + diff against develop
    tests/Redirects/check.py --base-ref origin/develop --live-check

Requires Docker and Python 3.11+ (standard library only).
"""

from __future__ import annotations

import argparse
import http.client
import os
import re
import re._parser as sre_parse  # noqa: PLC2701 - used to generate sample URLs from the rule patterns
import re._constants as sre_constants
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request
from dataclasses import dataclass, field
from pathlib import Path

DIR = Path(__file__).resolve().parent
ROOT = DIR.parent.parent
CONF_PATH = 'config/nginx/redirects.conf'
NGINX_IMAGE = os.environ.get('NGINX_IMAGE', 'nginx:1.27-alpine')
LIVE_HOST = 'https://docs.typo3.org'
LIVE_CHECK_LIMIT = 100
IN_CI = os.environ.get('GITHUB_ACTIONS') == 'true'

# Fill values for repetitions like (.*), the variants give each rule several sample URLs
FILLERS = ['', '/Index.html']
PREFIX_FILLERS = ['', 'x/1.0/Index.html', 'x']

Response = tuple[int, str]  # status code, Location header ('-' if none)
NOT_REDIRECTED: Response = (200, '-')


# --------------------------------------------------------------------------------------------------------------------
# Reporting
# --------------------------------------------------------------------------------------------------------------------

class Report:
    def __init__(self) -> None:
        self.errors = 0
        self.warnings = 0
        self.summary: list[str] = []

    def error(self, message: str, line: int | None = None) -> None:
        self.errors += 1
        self._annotate('error', message, line)

    def warning(self, message: str, line: int | None = None) -> None:
        self.warnings += 1
        self._annotate('warning', message, line)

    @staticmethod
    def _annotate(level: str, message: str, line: int | None) -> None:
        location = f'{CONF_PATH}:{line}: ' if line else ''
        print(f'{level.upper()}: {location}{message}')
        if IN_CI:
            file = f' file={CONF_PATH},line={line}' if line else ''
            print(f'::{level}{file}::{message}')

    def write_summary(self) -> None:
        target = os.environ.get('GITHUB_STEP_SUMMARY')
        if target and self.summary:
            with open(target, 'a', encoding='utf-8') as handle:
                handle.write('\n'.join(self.summary) + '\n')


# --------------------------------------------------------------------------------------------------------------------
# Parsing redirects.conf
# --------------------------------------------------------------------------------------------------------------------

@dataclass
class Rule:
    line: int
    modifier: str  # '=', '~', '~*', '^~' or '' (prefix)
    pattern: str
    status: int | None = None  # from "return"
    target: str | None = None  # from "return"
    rewrites: list[tuple[str, str, int]] = field(default_factory=list)  # (regex, replacement, status)
    regex: re.Pattern | None = None

    @property
    def label(self) -> str:
        return f'line {self.line} ({self.modifier} {self.pattern})'.replace('  ', ' ')

    def respond(self, uri: str) -> Response | None:
        """The response nginx sends for uri if this rule handles it, None if it cannot be computed."""
        if self.status is not None and self.target is not None:
            groups: tuple = ()
            if self.regex is not None:
                match = self.regex.search(uri)
                if match is None:
                    return None
                groups = match.groups()
            return self.status, substitute(self.target, groups)
        for regex, replacement, status in self.rewrites:
            match = re.search(regex, uri)
            if match:
                return status, substitute(replacement, match.groups())
        return None


def substitute(template: str, groups: tuple) -> str:
    """Replaces $1..$9 like nginx does, unmatched or missing groups become empty strings."""
    def replace(match: re.Match) -> str:
        index = int(match.group(1))
        return (groups[index - 1] or '') if index <= len(groups) else ''

    return re.sub(r'\$(\d)', replace, template)


LOCATION = re.compile(r'^\s*location\s+(?:(=|~\*|~|\^~)\s+)?(\S+)\s*\{\s*$')
RETURN = re.compile(r'^\s*return\s+(\d{3})\s+(\S+?)\s*;\s*$')
REWRITE = re.compile(r'^\s*rewrite\s+(\S+)\s+(\S+)(?:\s+(redirect|permanent|last|break))?\s*;\s*$')


def parse(text: str, report: Report | None = None) -> list[Rule]:
    rules: list[Rule] = []
    current: Rule | None = None
    for number, raw in enumerate(text.splitlines(), start=1):
        # "#" starts a comment only at the beginning of a word, "#anchor" in a target is no comment
        line = re.sub(r'(^|\s)#.*$', '', raw)
        if not line.strip():
            continue
        if current is None:
            match = LOCATION.match(line)
            if match:
                current = Rule(number, match.group(1) or '', match.group(2))
                if current.modifier in ('~', '~*'):
                    try:
                        flags = re.IGNORECASE if current.modifier == '~*' else 0
                        current.regex = re.compile(current.pattern, flags)
                    except re.error as exception:
                        if report:
                            report.error(f'Invalid regular expression: {exception}', number)
            elif report:
                report.warning(f'Unexpected line outside of a location block: {line.strip()}', number)
            continue
        if line.strip() == '}':
            rules.append(current)
            current = None
            continue
        if match := RETURN.match(line):
            current.status, current.target = int(match.group(1)), match.group(2)
        elif match := REWRITE.match(line):
            status = 301 if match.group(3) == 'permanent' else 302
            current.rewrites.append((match.group(1), match.group(2), status))
        elif report:
            report.warning(f'Unsupported directive, the rule is only syntax-checked: {line.strip()}', number)
    return rules


# --------------------------------------------------------------------------------------------------------------------
# 1. Lint
# --------------------------------------------------------------------------------------------------------------------

def lint(rules: list[Rule], report: Report) -> None:
    seen: dict[tuple[str, str], int] = {}
    for rule in rules:
        key = (rule.modifier, rule.pattern)
        if key in seen:
            report.error(f'Duplicate location, the one in line {seen[key]} always wins', rule.line)
        seen[key] = rule.line

        if rule.regex is not None and re.match(r'\^(?!/|\(|\\/)', rule.pattern):
            report.error('Pattern never matches: request paths always start with "/" after "^"', rule.line)

        targets = [(rule.target, rule.regex.groups if rule.regex else 0)] if rule.target else []
        targets += [(replacement, re.compile(regex).groups) for regex, replacement, _ in rule.rewrites]
        for target, groups in targets:
            used = [int(number) for number in re.findall(r'\$(\d)', target)]
            if used and max(used) > groups:
                report.error(f'Target uses ${max(used)}, but the pattern has only {groups} capture group(s)', rule.line)
            if re.search(r'[()]', target):
                report.error('Target contains a literal parenthesis', rule.line)
            path = re.sub(r'^https?://[^/]+', '', target).split('#', 1)[0]
            if '//' in path:
                report.error('Target contains a double slash', rule.line)
            if re.search(r'/permalink/\S*[^@]\$\d', target):
                report.error('Permalink version must be separated by "@", e.g. t3coreapi:label@$1', rule.line)


# --------------------------------------------------------------------------------------------------------------------
# 2. Sample URLs and nginx location selection
# --------------------------------------------------------------------------------------------------------------------

def generate(pattern: str, branch: int, filler: str) -> str:
    """Builds a string from a regex: alternative number <branch> of every group, <filler> for (.*)."""
    return _generate(sre_parse.parse(pattern), branch, filler)


def _generate(items, branch: int, filler: str) -> str:
    result = ''
    for op, value in items:
        if op is sre_constants.LITERAL:
            result += chr(value)
        elif op is sre_constants.NOT_LITERAL:
            result += 'y' if chr(value) == 'x' else 'x'
        elif op is sre_constants.ANY:
            result += '.'  # an unescaped "." in a path pattern is almost always meant as literal dot
        elif op is sre_constants.IN:
            result += _pick_from_class(value)
        elif op is sre_constants.CATEGORY:
            result += '1' if value is sre_constants.CATEGORY_DIGIT else 'a'
        elif op is sre_constants.BRANCH:
            alternatives = value[1]
            result += _generate(alternatives[min(branch, len(alternatives) - 1)], branch, filler)
        elif op is sre_constants.SUBPATTERN:
            result += _generate(value[-1], branch, filler)
        elif op in (sre_constants.MAX_REPEAT, sre_constants.MIN_REPEAT, sre_constants.POSSESSIVE_REPEAT):
            minimum, _, inner = value
            if len(inner) == 1 and inner[0][0] is sre_constants.ANY:
                result += filler if filler or minimum == 0 else 'x' * minimum
            else:
                count = minimum if not filler else max(minimum, 1)
                result += _generate(inner, branch, filler) * count
        # AT (anchors) and ASSERT/ASSERT_NOT (lookarounds) produce no characters,
        # lookarounds are checked afterwards by matching the generated string
    return result


def _pick_from_class(items) -> str:
    if items and items[0][0] is sre_constants.NEGATE:
        for candidate in 'xa1-':
            if re.fullmatch(_class_regex(items), candidate) is None:
                continue
            return candidate
        return 'x'
    op, value = items[0]
    if op is sre_constants.LITERAL:
        return chr(value)
    if op is sre_constants.RANGE:
        return chr(value[0])
    if op is sre_constants.CATEGORY:
        return '1' if value is sre_constants.CATEGORY_DIGIT else 'a'
    return 'x'


def _class_regex(items) -> str:
    """Rebuilds a negated character class like [^/] as regex to test candidates against it."""
    parts = []
    for op, value in items[1:]:
        if op is sre_constants.LITERAL:
            parts.append(re.escape(chr(value)))
        elif op is sre_constants.RANGE:
            parts.append(f'{re.escape(chr(value[0]))}-{re.escape(chr(value[1]))}')
    return f'[^{"".join(parts)}]' if parts else '.'


def samples(rule: Rule) -> list[str]:
    """Sample URLs that match the pattern of the rule (without regard to other rules)."""
    if rule.modifier == '=':
        return [rule.pattern]
    if rule.modifier in ('', '^~'):
        return [rule.pattern + filler for filler in PREFIX_FILLERS]
    if rule.regex is None:
        return []
    urls = []
    # One sample per alternative, so (main|13\.4|12\.4) is tested with main, 13.4 and 12.4
    for branch in range(max(1, _max_alternatives(sre_parse.parse(rule.pattern)))):
        for filler in FILLERS:
            # nginx merges duplicate slashes before matching locations (merge_slashes), samples are sent merged
            url = re.sub(r'/{2,}', '/', '/' + generate(rule.pattern, branch, filler))
            if rule.regex.search(url) and url not in urls:
                urls.append(url)
    return urls


def _max_alternatives(items) -> int:
    """The largest number of alternatives of any group in a parsed regex."""
    result = 0
    for op, value in items:
        if op is sre_constants.BRANCH:
            result = max(result, len(value[1]), *(_max_alternatives(alt) for alt in value[1]))
        elif op is sre_constants.SUBPATTERN:
            result = max(result, _max_alternatives(value[-1]))
        elif op in (sre_constants.MAX_REPEAT, sre_constants.MIN_REPEAT, sre_constants.POSSESSIVE_REPEAT):
            result = max(result, _max_alternatives(value[2]))
    return result


def select(rules: list[Rule], uri: str) -> Rule | None:
    """Simulates the nginx location selection: exact, then longest prefix (^~ stops), then regexes in order."""
    for rule in rules:
        if rule.modifier == '=' and rule.pattern == uri:
            return rule
    prefix = None
    for rule in rules:
        if rule.modifier in ('', '^~') and uri.startswith(rule.pattern):
            if prefix is None or len(rule.pattern) > len(prefix.pattern):
                prefix = rule
    if prefix is not None and prefix.modifier == '^~':
        return prefix
    for rule in rules:
        if rule.regex is not None and rule.regex.search(uri):
            return rule
    return prefix


def check_rules(rules: list[Rule], server: Nginx, report: Report) -> int:
    reached = 0
    for rule in rules:
        candidates = samples(rule)
        if not candidates:
            report.warning(f'No sample URL could be generated for {rule.pattern}, the rule is untested', rule.line)
            continue
        own = [url for url in candidates if select(rules, url) is rule]
        if not own:
            winner = select(rules, candidates[0])
            by = f'the rule in line {winner.line}' if winner else 'no rule'
            report.error(
                f'Rule is never reached: {candidates[0]} is handled by {by} first. '
                'Move more specific rules above more generic ones.', rule.line
            )
            continue
        reached += 1
        for url in own:
            expected = rule.respond(url)
            if expected is None:
                continue
            actual = server.probe(url)
            if actual != expected:
                report.error(
                    f'{url}: expected {expected[0]} {expected[1]}, nginx answered {actual[0]} {actual[1]}', rule.line
                )
    return reached


# --------------------------------------------------------------------------------------------------------------------
# nginx in Docker
# --------------------------------------------------------------------------------------------------------------------

class Nginx:
    def __init__(self, conf: Path, name: str) -> None:
        self.conf = conf
        self.name = f'redirects-{name}-{os.getpid()}'
        self.port = 0

    def mounts(self) -> list[str]:
        return [
            '-v', f'{DIR / "nginx.conf"}:/etc/nginx/nginx.conf:ro',
            '-v', f'{self.conf}:/etc/nginx/redirects.conf:ro',
        ]

    def syntax_check(self) -> subprocess.CompletedProcess:
        return subprocess.run(
            ['docker', 'run', '--rm', *self.mounts(), NGINX_IMAGE, 'nginx', '-t'],
            capture_output=True, text=True, check=False,
        )

    def __enter__(self) -> Nginx:
        subprocess.run(
            ['docker', 'run', '-d', '--rm', '--name', self.name, '-p', '127.0.0.1::80', *self.mounts(), NGINX_IMAGE],
            capture_output=True, check=True,
        )
        port = subprocess.run(['docker', 'port', self.name, '80/tcp'], capture_output=True, text=True, check=True)
        self.port = int(port.stdout.split('\n')[0].rsplit(':', 1)[1])
        for _ in range(30):
            try:
                self.probe('/')
                return self
            except OSError:
                time.sleep(0.5)
        raise RuntimeError(f'nginx container {self.name} did not start')

    def __exit__(self, *_) -> None:
        subprocess.run(['docker', 'stop', self.name], capture_output=True, check=False)

    def probe(self, uri: str) -> Response:
        connection = http.client.HTTPConnection('127.0.0.1', self.port, timeout=10)
        try:
            connection.request('GET', uri)
            response = connection.getresponse()
            return response.status, response.getheader('Location') or '-'
        finally:
            connection.close()

    def destination(self, uri: str) -> str | None:
        """Follows the redirects of this server, returns the first URL it does not redirect itself."""
        for _ in range(10):
            status, location = self.probe(uri)
            if status < 300 or status >= 400 or location == '-':
                return uri
            location = location.split('#', 1)[0]
            if location.startswith(LIVE_HOST):
                location = location[len(LIVE_HOST):] or '/'
            if not location.startswith('/'):
                return None  # external target
            uri = location
        return uri


# --------------------------------------------------------------------------------------------------------------------
# 3. Diff and 4. live check
# --------------------------------------------------------------------------------------------------------------------

def diff(base: Nginx, current: Nginx, urls: list[str], rules: list[Rule], report: Report, live: bool) -> None:
    changes = []
    for url in urls:
        before, after = base.probe(url), current.probe(url)
        if before != after:
            changes.append((url, before, after))

    print(f'== Diff against base: {len(changes)} of {len(urls)} URLs redirect differently')
    report.summary += ['## Redirect changes', '']
    if not changes:
        report.summary.append(f'No URL redirects differently ({len(urls)} URLs compared).')
        return

    live_results = check_live(current, changes, rules, report) if live else {}
    report.summary += [
        f'{len(changes)} of {len(urls)} URLs redirect differently. Please check that every change is intended.', '',
        '| URL | Before | After |' + (' Live destination |' if live else ''),
        '|---|---|---|' + ('---|' if live else ''),
    ]
    for url, before, after in changes:
        row = f'| `{url}` | {format_response(before)} | {format_response(after)} |'
        if live:
            row += f' {live_results.get(url, "")} |'
        report.summary.append(row)
        print(f'  {url}\n      before: {format_response(before)}\n      after:  {format_response(after)}')


def check_live(current: Nginx, changes, rules: list[Rule], report: Report) -> dict[str, str]:
    results: dict[str, str] = {}
    checked: dict[str, int | str] = {}
    for url, _, after in changes:
        if not 300 <= after[0] < 400:
            continue
        destination = current.destination(url)
        if destination is None:
            results[url] = 'external'
            continue
        if destination not in checked:
            if len(checked) >= LIVE_CHECK_LIMIT:
                results[url] = 'not checked (limit)'
                continue
            checked[destination] = fetch_live(destination)
        status = checked[destination]
        results[url] = f'{status} `{destination}`'
        if status == 404:
            rule = select(rules, url)
            report.warning(
                f'{url} ends at {LIVE_HOST}{destination}, which returns 404 '
                '(sample URL generated from the rule, captures may be filled with placeholders)',
                rule.line if rule else None,
            )
        elif not isinstance(status, int):
            report.warning(f'Could not check {LIVE_HOST}{destination}: {status}')
    return results


def fetch_live(path: str) -> int | str:
    request = urllib.request.Request(LIVE_HOST + path, headers={'User-Agent': 'intercept-redirect-check'})
    try:
        with urllib.request.urlopen(request, timeout=15) as response:
            return response.status
    except urllib.error.HTTPError as exception:
        return exception.code
    except (urllib.error.URLError, OSError) as exception:
        return str(exception)


def format_response(response: Response) -> str:
    status, location = response
    return f'{status} → `{location}`' if location != '-' else f'{status} (no redirect)'


# --------------------------------------------------------------------------------------------------------------------
# Main
# --------------------------------------------------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--base-ref', help='git revision to compare the redirects with, e.g. origin/develop')
    parser.add_argument('--live-check', action='store_true', help='check destinations of changed redirects live')
    args = parser.parse_args()

    report = Report()
    conf = ROOT / CONF_PATH
    rules = parse(conf.read_text(encoding='utf-8'), report)
    print(f'== {len(rules)} rules in {CONF_PATH}')

    lint(rules, report)

    current = Nginx(conf, 'current')
    syntax = current.syntax_check()
    if syntax.returncode != 0:
        report.error(f'nginx -t failed ({NGINX_IMAGE}):\n{syntax.stderr.strip()}')
        report.write_summary()
        return 1
    print(f'== nginx -t OK ({NGINX_IMAGE})')

    with tempfile.TemporaryDirectory() as temp, current:
        reached = check_rules(rules, current, report)
        print(f'== {reached} of {len(rules)} rules reached and answered as expected')

        if args.base_ref:
            base_conf = Path(temp) / 'redirects.conf'
            shown = subprocess.run(
                ['git', 'show', f'{args.base_ref}:{CONF_PATH}'], cwd=ROOT, capture_output=True, text=True, check=False
            )
            if shown.returncode != 0:
                report.warning(f'Skipping the diff, {CONF_PATH} not found in {args.base_ref}')
            else:
                base_conf.write_text(shown.stdout, encoding='utf-8')
                base = Nginx(base_conf, 'base')
                if base.syntax_check().returncode != 0:
                    report.warning(f'Skipping the diff, nginx -t fails for {CONF_PATH} in {args.base_ref}')
                else:
                    base_rules = parse(shown.stdout)
                    urls = list(dict.fromkeys(url for rule in base_rules + rules for url in samples(rule)))
                    with base:
                        diff(base, current, urls, rules, report, args.live_check)

    print(f'== {report.errors} error(s), {report.warnings} warning(s)')
    report.write_summary()
    return 1 if report.errors else 0


if __name__ == '__main__':
    sys.exit(main())
