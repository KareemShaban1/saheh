#!/bin/bash

php artisan serve --port=8005 &

php artisan queue:work &

php artisan reverb:start

