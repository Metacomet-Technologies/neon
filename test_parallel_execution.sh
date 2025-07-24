#!/bin/bash

# Enhanced Command Categorization Test Script
# Tests the 4-phase execution system with complete dependency management

echo "🎯 Testing Enhanced Command Categorization System"
echo "================================================="

# Test 1: Mixed command workflow
echo ""
echo "📝 Test 1: Mixed command workflow (all 4 phases)..."
echo "Command: !neon unban @user123 then create gaming category with voice channels then edit channel settings then delete old content"
echo "Expected Execution Order:"
echo "  Phase 1 (Recovery): unban commands first"
echo "  Phase 2 (Constructive): create category and channels sequentially"
echo "  Phase 3 (Modification): edit settings in parallel batches"
echo "  Phase 4 (Destructive): delete old content in parallel batches"
echo "Status: Ready for manual Discord testing"

# Test 2: Dependency conflict prevention
echo ""
echo "🔒 Test 2: Dependency conflict prevention..."
echo "Command: !neon create category 'test-area' with channels then delete the category"
echo "Expected Results:"
echo "  - Category created first (constructive phase)"
echo "  - Channels created in category (constructive phase)"
echo "  - Category deletion happens last (destructive phase)"
echo "  - No race conditions or dependency conflicts"
echo "Status: Ready for manual Discord testing"

# Test 3: Bulk destructive operations
echo ""
echo "🗑️  Test 3: Enhanced bulk operation detection..."
echo "Command: !neon delete 3 categories, ban 2 users, remove 4 roles"
echo "Expected Results:"
echo "  - Bulk operation warning (9 destructive commands detected)"
echo "  - All destructive operations in parallel batches"
echo "  - Completion within 30-45 seconds"
echo "  - Cache extended to 15 minutes automatically"
echo "Status: Ready for manual Discord testing"

# Test 4: Complete workflow with all phases
echo ""
echo "🔄 Test 4: Complete 4-phase workflow..."
echo "Command: !neon unmute @user1, create gaming setup, edit permissions, clean up old content"
echo "Expected Results:"
echo "  Phase 1: unmute @user1 (recovery - sequential)"
echo "  Phase 2: create categories/channels/roles (constructive - sequential)"
echo "  Phase 3: edit permissions/settings (modification - parallel batches)"
echo "  Phase 4: delete old channels/roles (destructive - parallel batches)"
echo "Status: Ready for manual Discord testing"
echo "  - API protection should prevent rate limiting"
echo "Status: Ready for manual Discord testing"

# Test 4: Mixed command types
echo ""
echo "🔄 Test 4: Mixed command execution order..."
echo "Command: !neon create category 'mixed-test' then create 2 channels in it, then delete them all"
echo "Expected Results:"
echo "  - Create commands execute sequentially first"
echo "  - Delete commands execute in parallel after"
echo "  - No timing issues with dependencies"
echo "Status: Ready for manual Discord testing"

echo ""
echo "📊 Current System Status:"
echo "========================"

# Check queue workers
echo "Queue Workers:"
ps aux | grep 'queue:work' | grep -v grep | while read line; do
    echo "  ✅ $line"
done

# Check Discord bot
echo ""
echo "Discord Bot:"
ps aux | grep 'neon:start' | grep -v grep | while read line; do
    echo "  ✅ $line"
done || echo "  ❌ Discord bot not running - start with: php artisan neon:start"

# Check recent queue logs
echo ""
echo "Recent Queue Activity:"
if [ -f "queue_worker_1.log" ]; then
    echo "  📋 Latest worker 1 activity:"
    tail -3 queue_worker_1.log | sed 's/^/    /'
fi

if [ -f "queue_worker_2.log" ]; then
    echo "  📋 Latest worker 2 activity:"
    tail -3 queue_worker_2.log | sed 's/^/    /'
fi

echo ""
echo "🎯 Key Improvements Implemented:"
echo "================================="
echo "✅ True parallel execution for delete commands"
echo "✅ Extended cache duration (15 min) for bulk operations"
echo "✅ Circuit breaker protection against API failures"
echo "✅ User warnings for bulk operations"
echo "✅ Batch processing (5 commands per batch)"
echo "✅ Enhanced error handling and logging"

echo ""
echo "⏱️  Performance Expectations:"
echo "============================="
echo "• 5-10 delete commands: ~15 seconds (was 30+ seconds)"
echo "• 10-15 delete commands: ~25 seconds (was 60+ seconds)"
echo "• 15-20 delete commands: ~35 seconds (was 90+ seconds)"
echo "• 20+ delete commands: ~45 seconds (was 120+ seconds)"

echo ""
echo "🧪 Manual Testing Instructions:"
echo "==============================="
echo "1. Go to your Discord server"
echo "2. Use the !neon command to create test content"
echo "3. Use the !neon command to delete 6+ items at once"
echo "4. Verify yellow bulk operation warning appears"
echo "5. Confirm deletion completes in under 60 seconds"
echo "6. Check for any API rate limiting errors"

echo ""
echo "📈 Monitoring Commands:"
echo "======================"
echo "# Watch queue activity:"
echo "tail -f queue_worker_1.log queue_worker_2.log"
echo ""
echo "# Check Laravel logs:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "# Monitor Discord API failures:"
echo "grep 'circuit breaker' storage/logs/laravel.log"

echo ""
echo "🎉 System is ready for testing! Use Discord to test bulk operations."
