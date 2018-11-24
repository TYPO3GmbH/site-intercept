#!/bin/bash
if [ "$(ps -p "$$" -o comm=)" != "bash" ]; then
    bash "$0" "$@"
    exit "$?"
fi

# fail immediately if some command failed
set -e
# output all commands
set -x

# Create log directory
mkdir -p var/php-cs-fixer/
mkdir -p var/phpunit/

# lint, phpunit, composer in docker helper functions
source Build/bamboo-container-functions.sh

# Check for PHP Errors
runLint

# Composer install dependencies using docker function
runComposer install --no-interaction --no-progress

# CGL Checks
runPhpCsFixer fix --config build/.php_cs.dist --format=junit > var/php-cs-fixer/php-cs-fixer.xml

# Unit tests
runPhpunit -c build/phpunit.xml --log-junit var/phpunit/phpunit.xml  --coverage-clover var/phpunit/coverage.xml --coverage-html var/phpunit/coverage/
