if [ "$(ps -p "$$" -o comm=)" != "bash" ]; then
    bash "$0" "$@"
    exit "$?"
fi

set -e
set -x

mkdir output
mkdir output10
mkdir output9
docker rmi ghcr.io/typo3gmbh/doxygenapi --force
git clone https://github.com/TYPO3GmbH/doxygenapi.git doxygen

cd doxygen
chmod +x all_filters.php

# Render master
echo -e "\nPROJECT_NAME           = TYPO3 CMS" >> Doxyfile
echo -e "\nPROJECT_NUMBER         = master" >> Doxyfile
cat Doxyfile
docker run \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/core:/mnt/doxygen \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/doxygen/:/mnt/doxyconf \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/output/:/mnt/output \
    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
    --rm \
     ghcr.io/typo3gmbh/doxygenapi /mnt/doxyconf/Doxyfile

# Render 10.4
echo -e "\nPROJECT_NUMBER         = 10.4" >> Doxyfile
cat Doxyfile
docker run \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/core:/mnt/doxygen \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/doxygen/:/mnt/doxyconf \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/output10/:/mnt/output \
    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
    --rm \
     ghcr.io/typo3gmbh/doxygenapi /mnt/doxyconf/Doxyfile

# Render 9.5
echo -e "\nPROJECT_NUMBER         = 9.5" >> Doxyfile
cat Doxyfile
docker run \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/core:/mnt/doxygen \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/doxygen/:/mnt/doxyconf \
    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/output9/:/mnt/output \
    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
    --rm \
     ghcr.io/typo3gmbh/doxygenapi /mnt/doxyconf/Doxyfile

# archive master
tar -cfz output.tgz output

# archive 10.4
tar -cfz output10.tgz output10

# archive 9.5
tar -cfz output9.tgz output9
