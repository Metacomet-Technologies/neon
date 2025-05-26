#!/bin/bash

rm -rf ./node_modules
rm -rf ./vendor

composer install
npm install

php artisan migrate
php artisan db:seed

composer run dev
