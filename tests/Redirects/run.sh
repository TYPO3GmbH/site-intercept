#!/usr/bin/env bash
#
# Verifies config/nginx/redirects.conf with a real nginx in Docker:
#   1. nginx -t (syntax check)
#   2. every URL in expectations.tsv answers with the expected status code and Location header
#
# Usage:
#   tests/Redirects/run.sh            check redirects.conf against expectations.tsv
#   tests/Redirects/run.sh --record   write the current behavior into expectations.tsv,
#                                     review the result with "git diff" before committing it
#
# expectations.tsv: one test per line, tab separated: <url> <status> <location>
#   <location> is "-" for responses without a Location header. Lines starting with "#" are comments.
#
# Environment:
#   NGINX_IMAGE  nginx Docker image (default: nginx:1.27-alpine)
#   PORT         local port for the test server (default: 18080)

set -euo pipefail

DIR=$(cd "$(dirname "$0")" && pwd)
ROOT=$(cd "$DIR/../.." && pwd)
CONF="$ROOT/config/nginx/redirects.conf"
EXPECTATIONS="$DIR/expectations.tsv"
IMAGE="${NGINX_IMAGE:-nginx:1.27-alpine}"
PORT="${PORT:-18080}"
NAME="redirects-test-$$"
RECORD=0
[[ "${1:-}" == "--record" ]] && RECORD=1

MOUNTS=(-v "$DIR/nginx.conf:/etc/nginx/nginx.conf:ro" -v "$CONF:/etc/nginx/redirects.conf:ro")

echo "== Syntax check (nginx -t, $IMAGE)"
docker run --rm "${MOUNTS[@]}" "$IMAGE" nginx -t

echo "== Starting nginx"
docker run -d --rm --name "$NAME" -p "127.0.0.1:$PORT:80" "${MOUNTS[@]}" "$IMAGE" >/dev/null
trap 'docker stop "$NAME" >/dev/null 2>&1 || true' EXIT

for _ in $(seq 1 30); do
    curl -s -o /dev/null "http://127.0.0.1:$PORT/" && break
    sleep 1
done

# Prints "<status><TAB><location>" for a URL, the location as sent by nginx or "-"
probe() {
    curl -s -o /dev/null -D - "http://127.0.0.1:$PORT$1" | tr -d '\r' | awk '
        NR == 1 { status = $2 }
        tolower($1) == "location:" { location = $2 }
        END { printf "%s\t%s", status, (location == "" ? "-" : location) }'
}

total=0
failures=0
recorded=$(mktemp)

while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ -z "$line" || "$line" == \#* ]]; then
        echo "$line" >> "$recorded"
        continue
    fi
    IFS=$'\t' read -r url expected_status expected_location <<< "$line"
    actual=$(probe "$url")
    total=$((total + 1))
    printf '%s\t%s\n' "$url" "$actual" >> "$recorded"

    if [[ $RECORD -eq 0 && "$actual" != "$expected_status"$'\t'"$expected_location" ]]; then
        failures=$((failures + 1))
        printf 'FAIL %s\n     expected: %s %s\n     actual:   %s\n' \
            "$url" "$expected_status" "$expected_location" "${actual/$'\t'/ }"
    fi
done < "$EXPECTATIONS"

if [[ $RECORD -eq 1 ]]; then
    mv "$recorded" "$EXPECTATIONS"
    echo "== Recorded $total URLs into $EXPECTATIONS, review the changes with: git diff $EXPECTATIONS"
    exit 0
fi
rm -f "$recorded"

echo "== $total URLs checked, $failures failed"
[[ $failures -eq 0 ]]
