#!/bin/sh
set -e

echo "Running database seeding..."
php artisan migrate:fresh --seed --force
echo "Database seeding completed"

return 0
