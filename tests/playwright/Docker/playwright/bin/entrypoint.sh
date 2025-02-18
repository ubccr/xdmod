#!/bin/bash

echo "Waiting for XDMoD container to spin up..."
$HOME/wait-for-it.sh -h xdmod -p 443 -- echo "XDMoD is up!"


pushd ${XDMOD_SOURCE_DIR}/tests/playwright || exit
echo "Installing -D @playwright/test..."
npm install -D @playwright/test
echo "playwright installed!"
echo "Updating playwright.config.ts..."
sed -i "s|'https://172.17.0.3/'|'https://xdmod'|g" playwright.config.ts
echo "Running playwright tests..."
BASE_URL=https://xdmod npx playwright test ${XDMOD_SOURCE_DIR}/tests/playwright/tests/* --workers=7
popd || exit
echo "Playwright tests complete!"
