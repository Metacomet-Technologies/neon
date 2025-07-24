# Neon Discord Bot

[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fa17fb849-c86a-4d48-9a4e-5756e7b3c718%3Fdate%3D1%26label%3D1%26commit%3D1&style=flat)](https://forge.laravel.com/servers/881111/sites/2755894)

🤖 **Production-Ready AI Discord Bot** with ChatGPT integration for intelligent server management.

## 🚀 Features

- **40+ Discord Commands**: Complete server management capabilities
- **ChatGPT Integration**: Natural language Discord server administration
- **Smart Command Execution**: 4-phase dependency-safe execution system
- **Bulk Operations**: Optimized parallel processing for large server operations
- **Context Awareness**: Understands current channel references and user intent
- **Production Ready**: Battle-tested with comprehensive error handling

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
## 🛠 Development Setup

### Quick Start
```bash
# Clone and setup
cp .env.example .env
composer install
npm install
php artisan key:generate

# Start development environment
./run.sh
```

### Environment Configuration

**Required API Keys:**
- **Discord**: [Developer Portal](https://discord.com/developers/applications)
  - `DISCORD_CLIENT_ID`
  - `DISCORD_CLIENT_SECRET` 
  - `DISCORD_BOT_TOKEN`
- **OpenAI**: [API Console](https://auth.openai.com/log-in)
  - `OPENAI_API_KEY`
  - `OPENAI_ORGANIZATION`

**Optional Services:**
- [Laravel Nova](https://nova.laravel.com): `NOVA_LICENSE_KEY`
- [Twitch API](https://dev.twitch.tv/console): `TWITCH_CLIENT_ID`, `TWITCH_CLIENT_SECRET`

### Development Tools
- **All-in-One Runner**: `./run.sh` - Starts Laravel queues, Discord bot, web server, and Vite
- **Queue Management**: Auto-restart on code changes with comprehensive logging
- **Testing Scripts**: Available in root directory for validation

## 📋 Available Commands

See [DISCORD_BOT_COMMANDS.md](DISCORD_BOT_COMMANDS.md) for complete command reference.

## 📊 System Status

See [SYSTEM_STATUS.md](SYSTEM_STATUS.md) and [FINAL_PRODUCTION_STATUS.md](FINAL_PRODUCTION_STATUS.md) for detailed system information.
        - [Stripe](https://dashboard.stripe.com/apikeys)
            - STRIPE_KEY
            - STRIPE_SECRET
            - STRIPE_WEBHOOK_SECRET
5. Run Local servers
    - `composer run dev`
