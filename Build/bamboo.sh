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
mkdir -p logs

# Set Environment Variables
touch .env

# lint, phpunit, composer in docker helper functions
source Build/bamboo-container-functions.sh

# Check for PHP Errors
runLint

# Composer install dependencies using docker function
runComposer install --no-interaction --no-progress

# CGL Checks
# Disabled for now since php-cs-fixer is not available
runPhpCsFixer fix --config Build/.php_cs.dist --format=junit > logs/php-cs-fixer.xml

# Unit tests
runPhpunit -c Build/UnitTests.xml --log-junit logs/phpunit.xml  --coverage-clover logs/coverage.xml --coverage-html logs/coverage/
