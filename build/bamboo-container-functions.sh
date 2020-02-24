#!/bin/bash

function runLint() {
    docker run \
        -u ${HOST_UID} \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \
        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \
        -e HOME=${HOME} \
        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
        --rm \
        typo3gmbh/php74:latest \
        bin/bash -c "cd ${PWD}; find . -name \*.php -print0 | xargs -0 -n1 -P2 php -n -c /etc/php/cli-no-xdebug/php.ini -d display_errors=stderr -l >/dev/null"
}

function runComposer() {
    docker run \
        -u ${HOST_UID} \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \
        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \
        -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} \
        -e HOME=${HOME} \
        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
        --rm \
        typo3gmbh/php74:latest \
        bin/bash -c "cd ${PWD}; composer $*"
}

function runPhpCsFixer() {
    docker run \
        -u ${HOST_UID} \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \
        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \
        -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} \
        -e HOME=${HOME} \
        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
        --rm \
        typo3gmbh/php74:latest \
        bin/bash -c "cd ${PWD}; ./bin/php-cs-fixer $*"
}

function runYarn() {
    docker run \
        -u ${HOST_UID} \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \
        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \
        -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} \
        -e HOME=${HOME} \
        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
        --rm \
        typo3gmbh/php74:latest \
        bin/bash -c "cd ${PWD}; yarn $*"
}

function runPhpunit() {
    docker run \
        -u ${HOST_UID} \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \
        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \
        -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} \
        -e HOME=${HOME} \
        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \
        --rm \
        typo3gmbh/php74:latest \
        bin/bash -c "cd ${PWD}; ./bin/phpunit $*"
}
