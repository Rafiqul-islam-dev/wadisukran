#!/bin/bash
set -e

export NVM_DIR="/root/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

nvm use 22

cd /home/wadishukran.com/public_html

echo "===== Pulling latest code ====="
git pull origin main

echo "===== Composer install ====="
composer install --no-dev --optimize-autoloader --no-interaction

echo "===== Running migrations ====="
php artisan migrate --force

echo "===== Building frontend assets ====="
rm -rf node_modules
npm ci
npm run build

echo "===== Clearing caches ====="
php artisan optimize:clear

echo "===== Permissions ====="
chmod -R 775 storage bootstrap/cache

echo "===== Deployment Finished ====="
