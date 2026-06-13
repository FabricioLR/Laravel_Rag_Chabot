#!/bin/bash

NOW="false"

case "$1" in
    --now)
        NOW="true"
        ;;
    "")
        NOW="false"
        ;;
    *)
        echo "Usage: $0 [--now]"
        exit 1
        ;;
esac

if [ "$NOW" = "true" ]; then
    docker exec -it laravel_app_dev php artisan app:fetch-word-press-posts
else
    docker exec -it laravel_app_dev php artisan schedule:work
fi

