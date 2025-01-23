#!/bin/bash

php artisan ide-helper:eloquent
php artisan ide-helper:generate
php artisan ide-helper:models -W -R
./vendor/bin/duster fix
prettier --write resources/js/**/*.tsx
npm run build
./vendor/bin/phpstan analyse
./vendor/bin/pest
