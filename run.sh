#!/bin/zsh
# Neon Herd local development runner
# Starts queue workers, bot, and web server for local development

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

WORKER_COUNT=${WORKER_COUNT:-2}
LOG_DIR="./storage/logs/dev"
mkdir -p "$LOG_DIR"

cleanup() {
    echo -e "${YELLOW}Shutting down...${NC}"
    jobs -p | xargs -r kill 2>/dev/null || true
    echo -e "${GREEN}Cleanup complete.${NC}"
}
trap cleanup EXIT INT TERM

main() {
    echo -e "${GREEN}Starting Neon Herd local dev...${NC}"

    # Install JS deps if needed
    if [ -f package.json ] && [ ! -d node_modules ]; then
        echo -e "${YELLOW}Installing JS dependencies...${NC}"
        npm install
    fi

    # Start Laravel queue workers
    for i in $(seq 1 $WORKER_COUNT); do
        echo -e "${YELLOW}Starting queue worker $i...${NC}"
        php artisan queue:work > "$LOG_DIR/worker_$i.log" 2>&1 &
    done

    # Start Discord bot
    if [ -f artisan ]; then
        echo -e "${YELLOW}Starting Discord bot (Neon)...${NC}"
        php artisan neon:start > "$LOG_DIR/discord_bot.log" 2>&1 &
    fi

    # Start web server
    if [ -f artisan ]; then
        echo -e "${YELLOW}Starting Laravel web server...${NC}"
        php artisan serve --host=127.0.0.1 --port=8000 > "$LOG_DIR/web.log" 2>&1 &
    fi

    # Start Vite dev server (if present)
    if [ -f vite.config.js ]; then
        echo -e "${YELLOW}Starting Vite dev server...${NC}"
        npm run dev > "$LOG_DIR/vite.log" 2>&1 &
    fi

    echo -e "${GREEN}All services started!${NC}"
    echo -e "${YELLOW}Logs: $LOG_DIR${NC}"
    echo -e "${YELLOW}Press Ctrl+C to stop everything.${NC}"
    wait
}

main
