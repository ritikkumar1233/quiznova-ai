#!/bin/sh

echo "Running migrations..."

php artisan migrate --force

echo "Creating storage link..."

php artisan storage:link || true

echo "Optimizing Laravel..."

php artisan optimize

echo "Starting Laravel server..."

php artisan serve --host=0.0.0.0 --port=10000 (See <attachments> above for file contents. You may not need to search or read the file again.)
