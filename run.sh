#!/bin/zsh
# Neon Herd local development runner
# Intelligently starts only the services that aren't already running

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

WORKER_COUNT=${WORKER_COUNT:-2}
LOG_DIR="./storage/logs/dev"
mkdir -p "$LOG_DIR"

# Function to check if a process is running
is_process_running() {
    local process_name="$1"
    pgrep -f "$process_name" > /dev/null 2>&1
}

# Function to count running queue workers
count_queue_workers() {
    pgrep -f "php artisan queue:work" | wc -l | tr -d ' '
}

# Function to check if port is in use
is_port_in_use() {
    local port="$1"
    lsof -i :$port > /dev/null 2>&1
}

cleanup() {
    echo -e "${YELLOW}Shutting down...${NC}"
    jobs -p | xargs -r kill 2>/dev/null || true
    echo -e "${GREEN}Cleanup complete.${NC}"
}
trap cleanup EXIT INT TERM

show_status() {
    echo -e "${BLUE}=== Current Service Status ===${NC}"

    # Check Discord bot
    if is_process_running "php artisan neon:start"; then
        echo -e "${GREEN}✓${NC} Discord Bot (Neon) - Running"
    else
        echo -e "${RED}✗${NC} Discord Bot (Neon) - Not running"
    fi

    # Check queue workers
    local worker_count=$(count_queue_workers)
    if [ "$worker_count" -gt 0 ]; then
        echo -e "${GREEN}✓${NC} Queue Workers - $worker_count running"
    else
        echo -e "${RED}✗${NC} Queue Workers - Not running"
    fi

    # Check web server
    if is_port_in_use 8000; then
        echo -e "${GREEN}✓${NC} Laravel Web Server - Running on port 8000"
    else
        echo -e "${RED}✗${NC} Laravel Web Server - Not running"
    fi

    # Check Vite dev server
    if is_port_in_use 5173; then
        echo -e "${GREEN}✓${NC} Vite Dev Server - Running on port 5173"
    else
        echo -e "${RED}✗${NC} Vite Dev Server - Not running"
    fi

    echo -e "${BLUE}===============================${NC}\n"
}

main() {
    echo -e "${GREEN}🚀 Neon Herd Smart Development Runner${NC}\n"

    # Show current status
    show_status

    # Install JS deps if needed
    if [ -f package.json ] && [ ! -d node_modules ]; then
        echo -e "${YELLOW}📦 Installing JS dependencies...${NC}"
        npm install
    fi

    # Check and start queue workers
    local current_workers=$(count_queue_workers)
    if [ "$current_workers" -lt "$WORKER_COUNT" ]; then
        local needed_workers=$((WORKER_COUNT - current_workers))
        echo -e "${YELLOW}⚡ Starting $needed_workers additional queue worker(s)...${NC}"

        for i in $(seq $((current_workers + 1)) $WORKER_COUNT); do
            echo -e "${BLUE}  → Starting queue worker $i${NC}"
            php artisan queue:work > "$LOG_DIR/worker_$i.log" 2>&1 &
        done
    else
        echo -e "${GREEN}✓ Queue workers already running ($current_workers/$WORKER_COUNT)${NC}"
    fi

    # Check and start Discord bot
    if ! is_process_running "php artisan neon:start"; then
        echo -e "${YELLOW}🤖 Starting Discord bot (Neon)...${NC}"
        php artisan neon:start > "$LOG_DIR/discord_bot.log" 2>&1 &
    else
        echo -e "${GREEN}✓ Discord bot already running${NC}"
    fi

    # Check and start web server
    if ! is_port_in_use 8000; then
        echo -e "${YELLOW}🌐 Starting Laravel web server...${NC}"
        php artisan serve --host=127.0.0.1 --port=8000 > "$LOG_DIR/web.log" 2>&1 &
    else
        echo -e "${GREEN}✓ Laravel web server already running on port 8000${NC}"
    fi

    # Check and start Vite dev server (if present)
    if [ -f vite.config.js ]; then
        if ! is_port_in_use 5173; then
            echo -e "${YELLOW}⚡ Starting Vite dev server...${NC}"
            npm run dev > "$LOG_DIR/vite.log" 2>&1 &
        else
            echo -e "${GREEN}✓ Vite dev server already running on port 5173${NC}"
        fi
    fi

    echo -e "\n${GREEN}🎉 All services checked and running!${NC}"
    echo -e "${BLUE}📁 Logs: $LOG_DIR${NC}"
    echo -e "${BLUE}🔗 Web: http://127.0.0.1:8000${NC}"
    if [ -f vite.config.js ]; then
        echo -e "${BLUE}⚡ Vite: http://127.0.0.1:5173${NC}"
    fi
    echo -e "${YELLOW}\n💡 Press Ctrl+C to stop everything.${NC}"
    echo -e "${YELLOW}💡 Run again to start any stopped services.${NC}\n"

    # Show final status
    show_status

    wait
}

main
