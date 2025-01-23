#!/bin/bash

PHP=php

if ! command -v $PHP &> /dev/null
then
    echo "php could not be found on PATH"
    echo "searching for php in Herd default location"
    if [ ! -f "$HOME/.config/herd/bin/php.bat" ]; then
        echo "php not found in Herd default location either"
        echo "please install php and add it to PATH"
        exit 1
    fi
    echo "php found in Herd default location at $HOME/.config/herd/bin/php.bat"
    echo "using Herd php"
    PHP="$HOME/.config/herd/bin/php.bat"
fi

echo ""
echo "Using PHP at $PHP"
echo ""

echo "Running ide-helper for Eloquent"
$PHP artisan ide-helper:eloquent

echo ""
echo "Running ide-helper for Laravel"
$PHP artisan ide-helper:generate

echo ""
echo "Running ide-helper for Models"
$PHP artisan ide-helper:models -W -R

echo ""
echo "Running duster fix"
$PHP ./vendor/bin/duster fix

echo ""
echo "Running prettier"
prettier --write resources/js/**/*.tsx

echo ""
echo "Running npm run build"
npm run build

echo ""
echo "Running larastan for static analysis"
$PHP ./vendor/bin/phpstan analyse --memory-limit=2G

echo ""
echo "Running pest for testing"
$PHP ./vendor/bin/pest
