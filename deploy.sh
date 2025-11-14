#!/bin/bash
cd /home/wadishukran.com/public_html

echo "===== Pulling latest code ====="
git pull origin main

echo "===== Composer install ====="
composer install --no-dev --optimize-autoloader

echo "===== Running migrations ====="
php artisan migrate --force

# Build frontend assets
npm install
npm run build

echo "===== Clearing caches ====="
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "===== Permissions ====="
chmod -R 775 storage bootstrap/cache

echo "===== Deployment Finished ====="
