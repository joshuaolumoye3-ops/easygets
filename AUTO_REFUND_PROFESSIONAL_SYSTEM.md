# Professional Auto-Expiration & Refund System

## Overview

This system automatically detects expired SMS activations (no SMS received after 5 minutes) and processes refunds through a professional, secure, and auditable process.

**Key Features:**
- ✅ Atomic database transactions (all-or-nothing)
- ✅ Idempotency checks (prevents double refunds)
- ✅ Pessimistic locking (prevents race conditions)
- ✅ Comprehensive audit trails (full compliance)
- ✅ Redundant safety checks (7-step verification)
- ✅ Professional error handling & alerts
- ✅ Balance verification before & after
- ✅ Operation ID tracking for troubleshooting

---

## How It Works

### Automatic Detection & Processing

When the cron job runs every minute, the scheduler triggers the auto-expiration process:

```
Every 1 minute:
  ├─ Check for pending activations > 5 minutes old
  ├─ For each eligible activation:
  │  ├─ Check status with provider API
  │  ├─ Determine if expired or still active
  │  ├─ Attempt cancellation if active
  │  └─ Process refund (see steps below)
  └─ Log all actions for audit trail
```

### 7-Step Professional Refund Process

When an expiration is detected, the system executes this bulletproof refund process:

#### **Step 1: Idempotency Check** 🔄
- Verifies no refund was already issued for this activation
- Prevents double-refunding if cron runs twice
- Checks last 5 minutes of refund history

```
If duplicate refund detected:
  → Log as informational
  → Return existing refund reference
  → Skip remaining steps
```

#### **Step 2: Activation Status Verification** ✓
- Double-checks activation still exists in database
- Locks activation row (pessimistic locking)
- Verifies status is still 'pending'

```
If status changed:
  → Skip refund (already processed)
  → Log reason (cancelled, completed, expired, etc.)
  → Return success but no refund
```

#### **Step 3: Status Update** 🏷️
- Atomically updates activation status → 'expired'
- Sets updated_at timestamp
- Marks activation as processed

```
Transaction check at each step:
  If update fails:
    → Throw exception
    → Entire transaction rolls back
    → CRITICAL alert sent
```

#### **Step 4: Wallet Validation & Locking** 🔐
- Checks wallet exists for user
- Applies pessimistic lock (SELECT ... FOR UPDATE)
- Prevents concurrent wallet modifications
- Records balance before refund

#### **Step 5: Balance Increment** 💰
- Increments wallet balance by refund amount
- Uses atomic SQL operation (balance + amount)
- Records balance after increment

#### **Step 6: Balance Verification** ✔️
- Fetches updated wallet balance
- Mathematically verifies: new = old + amount
- Within tolerance (±0.01 for float precision)

```
If verification fails:
  → Throw exception
  → Entire transaction rolls back  
  → Database remains consistent
  → CRITICAL alert sent
```

#### **Step 7: Audit Trail Creation** 📝
- Creates refund transaction record with:
  - Unique reference (REF_XXXXXXXXXX)
  - User ID
  - Amount
  - Description (reason for refund)
  - Operation ID (for troubleshooting)
  - Original activation ID
  - Wallet balance after refund
  - Timestamps

- Links refund to original activation
- Complete audit trail for compliance

---

## Safety Mechanisms

### Transaction Isolation

All steps happen in a single atomic transaction (ACID):
- **Atomicity**: All 7 steps succeed or ALL FAIL
- **Consistency**: Database never left in inconsistent state
- **Isolation**: Other processes can't interfere
- **Durability**: Once committed, permanent and traceable

```php
DB::transaction(function() {
    // All 7 steps here
    // If ANY step fails: automatic rollback
    // Database unchanged
}, attempts: 3, timeout: 30);
```

### Pessimistic Locking

Prevents race conditions when multiple cronjobs run:

```sql
-- Locks row so no other process can modify it
SELECT * FROM transactions WHERE id = ? FOR UPDATE;
SELECT * FROM wallets WHERE id = ? FOR UPDATE;
```

### Error Recovery

```
If transient error:
  → Automatically retry (up to 3 times)
  → Wait 30 seconds between retries
  → If all retries fail → Critical alert

If fatal error:
  → Transaction automatically rolls back
  → Database remains consistent
  → No orphaned records
  → CRITICAL log entry created
```

---

## Logging & Monitoring

### Log Channels

#### **Main Application Log** (`storage/logs/laravel.log`)
Normal operations, informational messages:
```
[2024-04-19 15:30:00] laravel.INFO: ✓✓✓ AUTO-REFUND COMPLETED SUCCESSFULLY ✓✓✓
[2024-04-19 15:30:01] laravel.INFO: Activation status updated to expired
```

#### **Alerts Log** (`storage/logs/alerts.log`)
Critical failures requiring admin intervention:
```
[2024-04-19 15:30:00] laravel.CRITICAL: ✗✗✗ AUTO-REFUND FAILED - MANUAL INTERVENTION REQUIRED ✗✗✗
[2024-04-19 15:30:00] laravel.ERROR: Refund Failure Alert
```

### Complete Log Entries

#### **Success Entry:**
```json
{
  "message": "✓✓✓ AUTO-REFUND COMPLETED SUCCESSFULLY ✓✓✓",
  "context": {
    "operation_id": "OP_60a1b2c3d",
    "activation_id": 12345,
    "user_id": 789,
    "amount": 500,
    "refund_reference": "REF_A1B2C3D4E5",
    "status_set_to": "expired",
    "description": "Auto-cancelled and refunded - No SMS received after 5 minutes",
    "timestamp": "2024-04-19T15:30:00+00:00"
  }
}
```

#### **Failure Entry:**
```json
{
  "message": "✗✗✗ AUTO-REFUND FAILED - MANUAL INTERVENTION REQUIRED ✗✗✗",
  "context": {
    "operation_id": "OP_60a1b2c3d",
    "activation_id": 12345,
    "user_id": 789,
    "amount": 500,
    "error": "Wallet not found for user_id: 789",
    "error_code": 0,
    "error_file": "/app/Console/Commands/ProcessExpiredActivations.php",
    "error_line": 245,
    "timestamp": "2024-04-19T15:30:00+00:00",
    "stack_trace": "..."
  }
}
```

---

## Monitoring & Verification

### Real-time Monitoring

```bash
# Watch successful refunds
tail -f /var/www/laravel/storage/logs/laravel.log | grep "✓✓✓ AUTO-REFUND COMPLETED"

# Watch failures
tail -f /var/www/laravel/storage/logs/alerts.log

# Search for specific activation
grep "activation_id.*12345" /var/www/laravel/storage/logs/laravel.log
```

### Database Verification

```sql
-- Check all refunds processed today
SELECT 
  id,
  reference,
  user_id,
  amount,
  status,
  description,
  created_at
FROM transactions
WHERE type = 'credit'
  AND channel = 'Refund'
  AND DATE(created_at) = CURDATE()
ORDER BY created_at DESC;

-- Verify wallet balances
SELECT 
  u.id as user_id,
  u.name,
  w.balance,
  COUNT(t.id) as refund_count,
  SUM(CASE WHEN t.type = 'credit' AND t.channel = 'Refund' THEN t.amount ELSE 0 END) as total_refunded
FROM users u
LEFT JOIN wallets w ON u.id = w.user_id
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.name, w.balance;

-- Check expired activations
SELECT 
  id,
  user_id,
  amount,
  status,
  created_at,
  updated_at,
  TIMESTAMPDIFF(MINUTE, created_at, updated_at) as processing_time_minutes
FROM transactions
WHERE type = 'activation'
  AND status = 'expired'
ORDER BY created_at DESC
LIMIT 20;
```

### Diagnostic Command

```bash
# Full system health check
php artisan activations:diagnose

# Test refund for specific activation
php artisan activations:test-refund --activation-id=12345

# Create test activation and verify refund
php artisan activations:test-refund --create-test
```

---

## Compliance & Audit Trail

### Complete Audit Trail

Every refund creates an immutable audit trail:

```
1. Activation record
   ├─ Original status: pending
   ├─ Final status: expired
   ├─ Created timestamp
   ├─ Updated timestamp
   └─ User reference

2. Refund transaction record
   ├─ Reference: REF_XXXXXXXXXX (unique identifier)
   ├─ User ID (who received refund)
   ├─ Amount (exact amount refunded)
   ├─ Type: credit
   ├─ Channel: Refund
   ├─ Description (reason)
   ├─ Status: success
   ├─ Balance after (wallet balance after refund)
   ├─ Response: JSON with operation ID & original activation ID
   ├─ Created timestamp
   └─ Updated timestamp

3. Application logs
   ├─ Operation ID (OP_XXXXXXXXXX)
   ├─ Step-by-step processing log
   ├─ Wallet balance before & after
   ├─ Error details (if any)
   ├─ Admin alerts (if failed)
   └─ Timestamps (ISO 8601 format)

4. Alert logs (if failure)
   ├─ Error description
   ├─ Stack trace
   ├─ System state
   ├─ Admin notifications
   └─ Timestamp
```

### Compliance Benefits

✅ **PCI DSS**: Complete financial transaction audit trail  
✅ **SOX**: Immutable, timestamped transaction records  
✅ **GDPR**: User transaction history available  
✅ **Internal Audit**: Full traceability of all refunds  
✅ **Dispute Resolution**: Complete record for chargebacks  

---

## Error Handling

### Types of Errors

#### **Transient Errors** (Automatic Retry)
- Database timeout
- Network timeout
- Temporary lock timeout
- API temporary failure

```
Action: Automatic retry (up to 3 attempts)
Impact: Transparent to users
```

#### **Validation Errors** (Skipped)
- Activation already expired/cancelled
- Wallet doesn't exist
- Status already changed
- Invalid amounts

```
Action: Log reason and skip
Impact: No refund issued (already handled)
```

#### **Fatal Errors** (Admin Alert)
- Database connection failure
- Balance verification failed
- Refund record creation failed
- Critical system issue

```
Action: Rollback transaction, send CRITICAL alert
Impact: Manual investigation required
Alert: CRITICAL log + admin notification
```

### Admin Alerts

When a refund fails critically:

1. **CRITICAL log entry** written to `alerts.log`
2. **Operation ID** provided for tracking
3. **All context** available for investigation
4. **Stack trace** included for debugging
5. **Manual action** required from admin

```
Alert includes:
  - Operation ID (to match logs)
  - Activation ID (what failed)
  - User ID (who affected)
  - Amount (₦value)
  - Error message (what went wrong)
  - Timestamp (when it happened)
  - Recommended action
```

---

## Setup & Configuration

### Cron Configuration

Add to crontab:
```bash
* * * * * cd /var/www/laravel && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1
```

### Environment Variables

```env
# Provider API tokens (REQUIRED for refunds to work!)
FIVESIM_TOKEN=your_5sim_api_token
SMSBUS_TOKEN=your_smsbus_api_token

# Logging
LOG_ALERTS_DAYS=30          # How long to keep alert logs
LOG_ALERTS_LEVEL=warning    # Minimum level for alerts
```

### Scheduler Configuration

In `app/Console/Kernel.php`:

```php
$schedule->command('activations:process-expired', ['--limit=50', '--timeout=5'])
    ->everyThreeMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('ProcessExpiredActivations command failed');
    });
```

---

## Troubleshooting

### Issue: Refund not processed

**Check:**
1. Cron running: `crontab -l`
2. Provider tokens in `.env`: `php artisan activations:diagnose`
3. Wallet exists: `php artisan activations:test-refund --user-id=123`
4. Logs for errors: `tail -f /var/www/laravel/storage/logs/laravel.log`

### Issue: Refund marked as FAILED in alerts

**Actions:**
1. Find operation ID in alerts log
2. Search application log for that operation ID
3. Identify specific step that failed
4. Manually verify wallet and transactions
5. Create manual refund if needed
6. Report specific error to support

### Issue: Wallet balance incorrect

**Verification:**
```sql
-- Check wallet balance
SELECT balance FROM wallets WHERE user_id = ?;

-- Sum all refunds for user
SELECT SUM(amount) FROM transactions 
WHERE user_id = ? AND type = 'credit' AND channel = 'Refund';

-- Check for duplicate refunds
SELECT COUNT(*) FROM transactions 
WHERE user_id = ? AND type = 'credit' AND channel = 'Refund';
```

---

## Performance Metrics

### Expected Performance

- **Processing time per activation**: ~2-3 seconds (includes API calls + DB operations)
- **Batch size**: 50 activations per run (configurable)
- **Scheduler frequency**: Every 3 minutes
- **Maximum concurrent runs**: 1 (prevented by locking)
- **Database transactions**: 1 per activation (atomic)

### Scalability

- Handles 50 refunds per 3-minute cycle
- = ~1,000 refunds per hour
- = ~24,000 refunds per day (on continuous activation)

If higher volume needed:
- Increase `--limit` parameter
- Run scheduler more frequently
- Increase API rate limits
- Monitor database performance

---

## Security Best Practices

✅ **Atomic Transactions**: All-or-nothing refunds  
✅ **Pessimistic Locking**: No race conditions  
✅ **Balance Verification**: Math validation before & after  
✅ **Idempotency**: No double refunds  
✅ **Operation IDs**: Complete traceability  
✅ **Encryption**: API tokens in .env  
✅ **Audit Trail**: Immutable transaction logs  
✅ **Error Logging**: CRITICAL when it fails  
✅ **Access Control**: CLI command only (not web-accessible)  

---

## Future Enhancements

- [ ] Email notifications to users when refunded
- [ ] Slack/Discord alerts for admin on refund failures  
- [ ] Webhook calls to external systems
- [ ] Refund reason customization
- [ ] Batch refund reports
- [ ] Dashboard for refund analytics
- [ ] Automatic retry with exponential backoff
- [ ] Refund performance metrics
- [ ] User notification preferences

---

## Support

For issues or questions about the auto-expiration refund system:

1. Check logs: `tail -f storage/logs/laravel.log`
2. Run diagnostics: `php artisan activations:diagnose`
3. Test refund: `php artisan activations:test-refund --create-test`
4. Review this guide (sections above)
5. Contact support with operation ID from logs
