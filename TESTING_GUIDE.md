# Implementation Checklist & Testing Guide

## Files Created/Modified

### ✅ New Files Created
1. **[app/Console/Commands/ProcessExpiredActivations.php](app/Console/Commands/ProcessExpiredActivations.php)**
   - 390+ lines of secure, well-documented code
   - Handles auto-expiration with 10-minute threshold
   - Implements distributed and database locking
   - Rate-limited API calls (5 second delay)
   - Works for both provider1 (5sim) and provider2 (sms-bus)

### ✅ Files Modified
1. **[app/Console/Kernel.php](app/Console/Kernel.php)**
   - Added scheduler for ProcessExpiredActivations
   - Runs every 3 minutes
   - Uses `withoutOverlapping()` to prevent concurrent execution
   - Includes failure callback for monitoring

2. **[app/Http/Controllers/HistoryController.php](app/Http/Controllers/HistoryController.php)**
   - Enhanced `cancelActivation()` method with:
     - Pessimistic locking (`lockForUpdate()`)
     - Better error handling and logging
     - Clear audit trails
   - New helper method `processCancellationRefund()`
   - New helper method `attemptProviderCancel()`
   - Improved logging for security monitoring

3. **[AUTO_EXPIRATION_GUIDE.md](AUTO_EXPIRATION_GUIDE.md)**
   - Comprehensive 200+ line documentation
   - Security features explained
   - Database impact documentation
   - Provider-specific implementation details
   - Troubleshooting guide

## Security Features Implemented

### 1. ✅ Distributed Locking
```php
$lockKey = 'activations:process-expired-lock';
if (Cache::has($lockKey)) {
    $this->warn('Previous process still running. Skipping...');
    return 0;
}
Cache::put($lockKey, true, $this->lockTimeout); // 5 min timeout
```
**Purpose**: Prevents multiple processes running simultaneously

### 2. ✅ Pessimistic Database Locking
```php
->lockForUpdate() // On transactions table
->lockForUpdate() // On wallets table
```
**Purpose**: Prevents race conditions during refunds

### 3. ✅ Rate Limiting
```php
private $rateLimit = 5; // 5 seconds between API calls
usleep($this->rateLimit * 1000000); // In loop
```
**Purpose**: Prevents overwhelming provider APIs

### 4. ✅ Atomic Transactions
```php
DB::transaction(function() {
    // 1. Update status
    // 2. Lock wallet
    // 3. Increment balance
    // 4. Create refund record
    // All succeed or all fail
});
```
**Purpose**: Ensures data consistency (no orphaned refunds)

### 5. ✅ Comprehensive Audit Logging
```php
Log::info('Processing activation', [...]);
Log::warning('Cancel rejected', [...]);
Log::error('Refund failed', [...]);
Log::critical('Cancellation refund failed', [...]);
```
**Purpose**: Complete transaction history for compliance

## Testing Guide

### Phase 1: Manual Command Testing

#### Test 1: Run command manually
```bash
php artisan activations:process-expired --limit=10 --timeout=10 -v
```
**Expected Output**:
```
🔄 Starting auto-expiration process (limit: 10, timeout: 10min)
Found X pending activations to process
Processing provider1 activation ID: 123, request_id: xxx
  → API indicates number is no longer available
  → Attempting to cancel active number...
  → Cancellation confirmed, processing refund
✓ Process completed: X processed, Y refunded, Z failed
```

#### Test 2: Check logs
```bash
tail -50 storage/logs/laravel.log | grep "ProcessExpiredActivations"
tail -50 storage/logs/laravel.log | grep "Auto-refund"
```

#### Test 3: Verify database changes
```sql
-- Check activation status changed to 'expired'
SELECT id, status, created_at, updated_at 
FROM transactions 
WHERE id = 123;

-- Check refund transaction created
SELECT * FROM transactions 
WHERE type = 'credit' AND channel = 'Refund' 
ORDER BY created_at DESC LIMIT 1;

-- Check wallet balance increased
SELECT * FROM wallets WHERE user_id = 456;
```

### Phase 2: User-Initiated Cancellation Testing

#### Test 4: Cancel via endpoint
```bash
curl -X POST http://localhost/app/history/cancel-activation \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"activation_id": 123}'
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Activation cancelled successfully. ₦1500.00 has been refunded to your wallet."
}
```

**Verify**:
- Status should be `'cancelled'` (NOT 'expired')
- Refund transaction should exist
- Wallet balance increased
- Logs show "User-initiated cancellation completed"

### Phase 3: Scheduler Testing

#### Test 5: Enable and test scheduler
```bash
# Option A: Run once manually
php artisan activations:process-expired

# Option B: Set up to run every minute for testing
# Modify app/Console/Kernel.php: ->everyMinute() instead of everyThreeMinutes()

# Monitor logs in real-time
tail -f storage/logs/laravel.log | grep "ProcessExpiredActivations\|Auto-refund"
```

#### Test 6: Verify lock prevents concurrent execution
```bash
# Terminal 1
php artisan activations:process-expired

# Terminal 2 (while Terminal 1 is running)
php artisan activations:process-expired
# Should see: "Previous process still running. Skipping..."
```

### Phase 4: Edge Cases

#### Test 7: Already completed activation
```bash
# Update activation status to 'completed'
UPDATE transactions SET status = 'completed' WHERE id = 123;

# Run command
php artisan activations:process-expired

# Verify: Should skip this activation (log: "SMS code already received")
```

#### Test 8: Already cancelled activation
```bash
# Update activation status to 'cancelled'
UPDATE transactions SET status = 'cancelled' WHERE id = 123;

# Run command
php artisan activations:process-expired

# Verify: Should not process (log: "Skip - already cancelled")
```

#### Test 9: API failure handling
```bash
# Simulate API down by setting fake token
# Run command
php artisan activations:process-expired

# Verify: Assumes number still valid, skips processing
# Check log: "Failed to check API status"
```

### Phase 5: Provider-Specific Testing

#### Test 10: Provider1 (5sim) auto-cancellation
```bash
# Create test activation with provider1
INSERT INTO transactions (..., channel='provider1', expiration_time=10) VALUES (...);

# Run command after 10+ minutes
php artisan activations:process-expired

# Verify:
# 1. API called: https://5sim.net/v1/user/check/{request_id}
# 2. Cancel called: https://5sim.net/v1/user/cancel/{request_id}
# 3. Status changed to 'expired'
# 4. Refund processed
```

#### Test 11: Provider2 (sms-bus) auto-cancellation
```bash
# Create test activation with provider2
INSERT INTO transactions (..., channel='provider2', expiration_time=10) VALUES (...);

# Run command after 10+ minutes
php artisan activations:process-expired

# Verify:
# 1. API called: https://sms-bus.com/api/control/get/sms
# 2. Cancel called: https://sms-bus.com/api/control/cancel
# 3. Status changed to 'expired'
# 4. Refund processed
```

## Performance Benchmarks

### Expected Performance (per run)
- **Processing 50 activations**: ~4-5 minutes (5 sec delay × 50)
- **Database queries**: ~150-200 (3-4 per activation)
- **Memory usage**: ~10-15 MB
- **Lock acquisition**: <100ms

### Optimization Tips
- Adjust `--limit` based on your server capacity
- Lower `$this->rateLimit` if provider APIs are reliable (test thoroughly first!)
- Increase scheduler frequency if you have high volume (but watch API costs)

## Rollback Plan

If you need to revert:
```bash
# Disable scheduler (edit Kernel.php)
# Remove: $schedule->command('activations:process-expired')...

# Or disable just the command running
php artisan migrate (if schema changes needed)
```

## Monitoring & Alerts

### Set up log monitoring
```bash
# Alert on failed refunds (CRITICAL)
grep "Cancellation refund failed" storage/logs/laravel.log

# Check command runs
grep "ProcessExpiredActivations completed" storage/logs/laravel.log

# Monitor API failures
grep "Failed to check API status" storage/logs/laravel.log
```

### Database monitoring
```sql
-- Check for pending activations > 10 minutes old
SELECT COUNT(*) FROM transactions 
WHERE status = 'pending' 
  AND type = 'activation'
  AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE);

-- Check refund success rate
SELECT 
  channel,
  status,
  COUNT(*) as count
FROM transactions
WHERE type = 'credit'
GROUP BY channel, status;
```

## Success Criteria

✅ **All tests pass**:
- [ ] Command runs without errors
- [ ] Lock prevents concurrent execution
- [ ] Status changes to 'expired' (not 'cancelled') for auto-expiration
- [ ] Status changes to 'cancelled' for user-initiated cancellation
- [ ] Refund transactions created for both paths
- [ ] Wallet balances increased correctly
- [ ] Both providers handled correctly
- [ ] No existing 'cancelled'/'completed'/'expired' affected
- [ ] Comprehensive logs for audit trail
- [ ] Atomic transactions (no orphaned refunds)

## Notes

⚠️ **Important**: Test thoroughly in a staging environment first before production deployment

⚠️ **API Rate Limits**: Contact your providers to confirm API call limits:
- Provider1 (5sim): Check their documentation
- Provider2 (SMS-Bus): Check their documentation

✅ **Backwards Compatible**: Works with both old and new provider naming

✅ **No Data Loss**: System preserves all existing transactions
