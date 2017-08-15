#!/usr/bin/env bash

set -e
START_TIME=$(date +%s.%3N)


# Ensure that this is being run inside a CI container
if [ "${CI}" != "true" ]; then
    echo "This script is designed to run inside a CI container only. Exiting"
    exit 1
fi

PHP_VER=$(phpenv version-name)

echo "PHP Version: ${PHP_VER}"

if [[ $PHP_VER == 7* ]]; then
    pecl install -f yaml-2.0.2  < /dev/null
else
    pecl install -f yaml  < /dev/null
fi

echo "Installed yaml."
