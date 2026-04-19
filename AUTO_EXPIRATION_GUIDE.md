# Secure Auto-Expiration and Refund System

## Overview
This document describes the secure, rate-limited automatic expiration and refund system for SMS activation numbers that haven't received an OTP within 10 minutes.

## Status Definitions

### Status: `pending`
- Initial state when activation is created
- SMS has not been received yet
- Number is waiting for verification

### Status: `cancelled`
- **User-initiated**: User manually cancelled the activation
- Refund is processed immediately
- Provider receives cancellation request

### Status: `expired`
- **System auto-expiration**: Triggered after 10 minutes of inactivity (no SMS code)
- Automatic process runs every 3 minutes
- Refund is processed automatically after provider cancellation
- Does NOT affect existing "cancelled", "completed", or "expired" transactions

### Status: `completed`
- SMS code was successfully received
- Cannot be cancelled or expired

## How It Works

### 1. User-Initiated Cancellation (Manual)
```
User clicks Cancel → POST /history/cancel-activation
                  ↓
         Check provider at API
                  ↓
      Cancel request sent to provider
                  ↓
    Cancellation confirmed by provider
                  ↓
      Update status to "cancelled" ✓
      Refund user amount
      Create refund transaction
```

**Result**: Status = `cancelled`

### 2. Automatic Expiration (Background)
```
Scheduled command runs every 3 minutes
                  ↓
    Find pending activations > 10 minutes old
                  ↓
      Check current status with provider API
                  ↓
        Has SMS code received?
        ├─ YES → Skip (still waiting)
        └─ NO → Continue
                  ↓
    Is number still active on provider?
    ├─ NO (expired/released) → Refund & Mark expired
    └─ YES → Attempt to cancel
                  ↓
      Cancel confirmed by provider?
      ├─ YES → Refund & Mark expired ✓
      └─ NO → Log error, try next run
```

**Result**: Status = `expired`

## Command Details

### Schedule
- **Frequency**: Every 3 minutes
- **Concurrency**: Prevents overlapping executions
- **Lock Timeout**: 5 minutes (maximum processing time)
- **Rate Limiting**: 5 second delay between API calls per provider

### Command Options
```bash
# Run with custom settings
php artisan activations:process-expired --limit=50 --timeout=10

# Options:
--limit=50      : Max activations to process per run (default: 50)
--timeout=10    : Minutes before auto-expiring (default: 10)
```

### Security Features

#### 1. **Distributed Locking**
- Prevents concurrent execution of the same command
- Uses Cache for lock management
- Lock timeout: 5 minutes

#### 2. **Database Locking**
- Pessimistic locking on transactions table
- Prevents race conditions during status updates
- Wallet balance increments are atomic

#### 3. **Rate Limiting**
- 5-second delay between provider API calls
- Prevents overwhelming provider APIs
- Configurable per environment

#### 4. **Validation**
- Only processes pending activations
- Skips already completed/cancelled/expired
- Validates wallet exists before refunding

#### 5. **Error Handling**
- Graceful fallback on API failures
- Assumes number still valid if API check fails
- Comprehensive error logging for audit trials

#### 6. **Audit Trail**
- All transactions logged with reason
- Refund transactions include description
- Separate logging for auto vs user-initiated

## Database Impact

### Activation Record
```
id  status      created_at              expires_at
1   pending     2024-01-15 10:00:00    2024-01-15 10:20:00
↓ (if SMS received)
    completed   (updated)
↓ (if user cancels or auto-expires)
    cancelled   (user-initiated)
    expired     (auto-expiration)
```

### Refund Transaction
```
type: 'credit'
channel: 'Refund'
description: "User-initiated cancellation refund..." OR
             "Auto-cancelled and refunded - No SMS received..."
status: 'success'
```

## Provider-Specific Implementation

### Provider1 (5sim)
```
Check: GET /v1/user/check/{request_id}
Cancel: GET /v1/user/cancel/{request_id}

Status mapping:
- RECEIVED/PENDING → Still valid, skip
- FINISHED → No SMS, mark expired
- CANCELED → Already cancelled
- TIMEOUT → Expired
```

### Provider2 (SMS-Bus)
```
Check: GET /api/control/get/sms?token={token}&request_id={id}
Cancel: GET /api/control/cancel?token={token}&request_id={id}

Code mapping:
- 200 + data → SMS received, skip
- 50101 → Still pending, skip
- 50102 → Expired/released, mark expired
- 50103 → Already closed, treat as success
```

## Manual Testing

### Run command once (no schedule)
```bash
php artisan activations:process-expired --limit=10 --timeout=10
```

### Run with verbose output
```bash
php artisan activations:process-expired --limit=10 --timeout=10 -v
```

## Monitoring & Troubleshooting

### Check logs
```bash
tail -f storage/logs/laravel.log | grep ProcessExpiredActivations
tail -f storage/logs/laravel.log | grep "Auto-refund"
```

### Check specific activation
```sql
-- View activation and refund records
SELECT * FROM transactions 
WHERE type = 'activation' 
  AND id = 123
ORDER BY updated_at DESC;

-- View refunds for user
SELECT * FROM transactions 
WHERE user_id = 456 
  AND type = 'credit' 
  AND channel = 'Refund'
ORDER BY created_at DESC;
```

### Verify wallet balance increased
```sql
SELECT * FROM wallets WHERE user_id = 456;
```

## Important Notes

⚠️ **DO NOT** manually cancel existing transactions with status `expired`, `cancelled`, or `completed` - they represent final states

⚠️ The command checks the current API status before taking action - if the API is temporarily down, processing is skipped (tries again in 3 minutes)

⚠️ Refund transactions have status `success` to indicate the refund completed - not to be confused with activation `success`

✓ The system is backwards compatible - old transactions with channel='5sim' or 'smsbus' are normalized to 'provider1'/'provider2'

⚠️ **Rate Limiting**: Set to 5 seconds between API calls - modify `$this->rateLimit` in the command if provider APIs can handle faster rates

✓ **Scalability**: The system uses:
- Database-level locking
- Cache-based distributed locking
- Batch processing (limit configurable)
- Efficient query with early termination

## Cost Savings

This system automatically captures failed/unused numbers after 10 minutes and refunds users:
- **User satisfaction**: No manual support tickets for expired numbers
- **Trust**: Immediate refund without user effort
- **Efficiency**: Prevents locked funds on the platform
- **Audit**: Complete transaction history for compliance

## Example Log Entry

```
[2024-01-15 10:15:00] local.INFO: ProcessExpiredActivations completed {
  "total": 50,
  "processed": 47,
  "refunded": 45,
  "failed": 2
}

[2024-01-15 10:15:02] local.INFO: Auto-refund processed {
  "activation_id": 123,
  "user_id": 456,
  "amount": 1500,
  "reason": "Auto-cancelled and refunded - No SMS received after 10 minutes"
}
```
