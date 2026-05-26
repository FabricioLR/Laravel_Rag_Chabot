#! /bin/bash

docker exec -it laravel_app_dev php artisan app:view-word-press-sync-state $1