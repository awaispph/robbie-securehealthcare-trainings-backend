#!/bin/bash

# Create storage directory structure
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
mkdir -p storage/logs
mkdir -p storage/app/public

# Create .gitkeep files
touch storage/framework/cache/data/.gitkeep
touch storage/framework/sessions/.gitkeep
touch storage/framework/views/.gitkeep
touch storage/framework/testing/.gitkeep
touch storage/logs/.gitkeep
touch storage/app/public/.gitkeep
touch storage/app/.gitkeep

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create symbolic link
php artisan storage:link

# Optimize the application
php artisan optimize:clear

# create database and run migrations

echo "Storage directory structure has been created successfully!"
