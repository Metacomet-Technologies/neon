# Neon Bot

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fa17fb849-c86a-4d48-9a4e-5756e7b3c718%3Fdate%3D1%26label%3D1%26commit%3D1&style=flat)](https://forge.laravel.com/servers/881111/sites/2755894)

AI augmented discord bot and website for better server management.

## How to contribute

1. Create .env file from the example - `cp .env.example .env`.
2. Install nodemon
    - `npm i nodemon -g`
3. Install Project Dependencies
    - PHP - Composer
        - `composer install`
    - Javascript - npm
        - `npm install`
4. Set environment variables for your local setup.
    - Suggested Setups
        - Herd
            - Run init command for recommended setup `herd init`.
        - Sail
            - TODO: create sail docker-compose.yml
    - Hints on where to find values for env keys
        - Artisan Commands
            - APP_KEY - Run `php artisan key:generate`
        - [Discord Developer Portal](https://discord.com/developers/applications)
            - DISCORD_CLIENT_ID
            - DISCORD_CLIENT_SECRET
            - DISCORD_BOT_TOKEN
        - [OpenAI API Console](https://auth.openai.com/log-in)
            - OPENAI_API_KEY
            - OPENAI_ORGANIZATION
        - [Laravel Nova](https://nova.laravel.com)
            - NOVA_LICENSE_KEY
        - [Twitch Developer Portal](https://dev.twitch.tv/console)
            - TWITCH_CLIENT_ID
            - TWITCH_CLIENT_SECRET
        - [Laravel Spark](https://spark.laravel.com)
            - SPARK_LICENSE_KEY
        - [Stripe](https://dashboard.stripe.com/apikeys)
            - STRIPE_KEY
            - STRIPE_SECRET
            - STRIPE_WEBHOOK_SECRET
5. Run Local servers
    - `composer run dev`
