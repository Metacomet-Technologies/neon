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

$PHP artisan code:format
