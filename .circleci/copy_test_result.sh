#!/bin/bash

# Define the OS parameter value
OS="$1"  # The OS value passed as the first argument

# Logging the start of the script
echo "Starting the file copy process for OS: $OS"

# Execute the docker cp commands with logging
echo "Copying phpunit directory..."
docker cp xdmod:/root/phpunit ~/phpunit

echo "Copying screenshots directory..."
docker cp xdmod:/tmp/screenshots /tmp/screenshots

echo "Creating log directory..."
mkdir -p ~/project/log

echo "Copying xdmod logs..."
docker cp xdmod:/var/log/xdmod ~/project/log

echo "Copying php-fpm logs..."
docker cp xdmod:/var/log/php-fpm/ ~/project/log

echo "Copying Playwright test results for OS: $OS..."
docker cp playwright:/root/xdmod/tests/playwright/test_results-${OS}.xml ~/phpunit

echo "Copying Playwright test-results screenshots..."
docker cp playwright:/root/xdmod/tests/playwright/test-results /tmp/screenshots

# Logging the completion of the script
echo "File copy process completed for OS: $OS"
