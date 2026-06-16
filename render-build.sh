#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

echo "Installing Node dependencies & building Vite assets..."
npm install
npm run build

echo "Caching Laravel configuration..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running Database Migrations..."
php artisan migrate --force