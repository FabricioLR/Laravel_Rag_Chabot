#!/bin/bash
if [ ! -d "vendor" ]; then
    composer install --no-interaction --optimize-autoloader
fi
if [ ! -d "node_modules" ]; then
    npm install
fi
npm run dev -- --host &
exec "$@"