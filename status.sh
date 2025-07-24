#!/bin/zsh
# Neon Herd development status checker
# Shows what services are currently running

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

echo -e "${BLUE}🔍 Neon Development Status${NC}\n"

echo -e "${BLUE}=== Service Status ===${NC}"

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

echo -e "${BLUE}=====================${NC}\n"

# Show useful links for running services
echo -e "${BLUE}🔗 Quick Links:${NC}"
if is_port_in_use 8000; then
    echo -e "${GREEN}  → Web: http://127.0.0.1:8000${NC}"
fi
if is_port_in_use 5173; then
    echo -e "${GREEN}  → Vite: http://127.0.0.1:5173${NC}"
fi

echo -e "\n${YELLOW}💡 Run './run.sh' to start any missing services${NC}"
echo -e "${YELLOW}💡 Check logs in: ./storage/logs/dev/${NC}"
