# Auto-Expiration System - Setup & Troubleshooting Guide

## ⚠️ Critical: Provider Tokens Configuration

**This is the #1 reason auto-expiration doesn't work!**

### Required Environment Variables

Your `.env` file MUST contain:

```env
FIVESIM_TOKEN=your_5sim_api_token
SMSBUS_TOKEN=your_smsbus_api_token
```

### How to Get Provider Tokens

#### For 5sim (Provider1):
1. Go to https://5sim.net/en/settings/api
2. Login with your 5sim account
3. Copy your API token
4. Add to `.env`: `FIVESIM_TOKEN=your_token`

#### For SMS-Bus (Provider2):
1. Login to your SMS-Bus provider dashboard
2. Find API settings
3. Copy your API token
4. Add to `.env`: `SMSBUS_TOKEN=your_token`

### Verify Tokens Are Set

Run this command on your server:
```bash
php artisan activations:diagnose
```

You should see:
```
✓ FIVESIM_TOKEN: ✓ Set
✓ SMSBUS_TOKEN: ✓ Set
```

---

## 🛠️ Complete Server Setup Checklist

### 1. Correct Cron Configuration

Your **ONLY** cron entry should be:

```bash
* * * * * cd /var/www/laravel && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1
```

**DO NOT** have multiple direct command entries like:
```bash
# ❌ WRONG - Remove these!
* * * * * cd /var/www/laravel && php artisan otp:check-pending-orders
* * * * * cd /var/www/laravel && php artisan activations:process-expired
```

### 2. Verify Cron Is Running

SSH into server:
```bash
ssh root@srv1518877
```

Check crontab:
```bash
crontab -l
```

Should show:
```
* * * * * cd /var/www/laravel && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1
```

If cron service isn't running:
```bash
# For Ubuntu/Debian
sudo systemctl start cron
sudo systemctl enable cron

# For CentOS/RHEL
sudo systemctl start crond
sudo systemctl enable crond
```

### 3. Configure Environment Variables

Edit `.env`:
```bash
nano /var/www/laravel/.env
```

Add/update:
```env
# Provider API Tokens (CRITICAL!)
FIVESIM_TOKEN=your_5sim_token_here
SMSBUS_TOKEN=your_smsbus_token_here

# Cache configuration (for locking system)
CACHE_DRIVER=redis  # or 'file' if redis not available

# Database (verify this works)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass

# Logging (recommended for debugging)
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### 4. Clear Cache After .env Changes

```bash
php artisan config:cache
php artisan cache:clear
```

---

## 🔍 Testing & Verification

### Step 1: Run Diagnostics

```bash
cd /var/www/laravel
php artisan activations:diagnose
```

Check for:
- ✓ Database connection: OK
- ✓ Cache system: OK
- ✓ FIVESIM_TOKEN: ✓ Set
- ✓ SMSBUS_TOKEN: ✓ Set
- ✓ wallets table: EXISTS

### Step 2: Create Test Activation

Interactive mode will guide you:
```bash
php artisan activations:test-refund --create-test
```

This creates:
- A pending activation (6 minutes old = eligible for refund)
- Wallet with ₦10,000 balance

Then run:
```bash
php artisan activations:process-expired -v
```

Expected output:
```
🔄 Starting auto-expiration process (limit: 50, timeout: 5min)
Found 1 pending activations to process
Processing provider1 activation ID: 123, request_id: abc123xyz
  → API indicates number is no longer available (expired/released)
  ✓ REFUND SUCCESS - Reference: REF_A1B2C3D4E5 | Amount: ₦500.00
✓ Process completed: 1 processed, 1 refunded, 0 failed
```

### Step 3: Verify Refund

Check if refund was created:
```sql
-- SSH into MySQL
mysql -u your_user -p your_database

-- Check refund transaction
SELECT * FROM transactions 
WHERE type = 'credit' 
  AND channel = 'Refund' 
ORDER BY created_at DESC 
LIMIT 1;

-- Check wallet balance increased
SELECT user_id, balance FROM wallets 
WHERE id = 1;
```

---

## 📊 Monitoring Auto-Expiration

### Real-time Logs

```bash
# Watch scheduler
tail -f /var/log/laravel-schedule.log

# Watch application logs
tail -f /var/www/laravel/storage/logs/laravel.log | grep -i "process-expired\|refund\|auto"
```

### Diagnostics Command

```bash
# Full system check
php artisan activations:diagnose

# Test refund for specific activation
php artisan activations:test-refund --activation-id=123

# Check specific user's activations
php artisan activations:test-refund --user-id=456
```

### Database Queries

```sql
-- Check pending activations eligible for refund (>= 5 mins old)
SELECT id, user_id, amount, TIMESTAMPDIFF(MINUTE, created_at, NOW()) as age_minutes
FROM transactions
WHERE type = 'activation'
  AND status = 'pending'
  AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
ORDER BY created_at ASC;

-- Check recent refunds
SELECT id, reference, user_id, amount, status, description, created_at
FROM transactions
WHERE type = 'credit'
  AND channel = 'Refund'
ORDER BY created_at DESC
LIMIT 20;

-- Check expired activations
SELECT id, user_id, amount, status, created_at
FROM transactions
WHERE type = 'activation'
  AND status = 'expired'
ORDER BY created_at DESC
LIMIT 20;

-- Check wallet balances
SELECT id, user_id, balance, updated_at
FROM wallets
ORDER BY updated_at DESC
LIMIT 10;
```

---

## 🚨 Troubleshooting

### Issue: "Found 0 pending activations to process"

**Possible Causes:**
1. No pending activations in the system (normal if users completed them)
2. All pending > 5 mins have already been processed

**Fix:**
```bash
# Check if pending activations exist
php artisan activations:test-refund --create-test

# Run command manually
php artisan activations:process-expired -v
```

### Issue: "FIVESIM_TOKEN: NOT SET" / "SMSBUS_TOKEN: NOT SET"

**Root Cause:** Tokens not in `.env` file

**Fix:**
```bash
# 1. Edit .env
nano /var/www/laravel/.env

# 2. Add tokens:
FIVESIM_TOKEN=your_actual_token
SMSBUS_TOKEN=your_actual_token

# 3. Clear cache
php artisan config:cache

# 4. Verify
php artisan activations:diagnose
```

### Issue: Refund created but wallet balance not updated

**Possible Causes:**
1. Wallet doesn't exist for user
2. Database transaction failed
3. Lock timeout

**Fix:**
```bash
# Check wallet exists
php artisan activations:test-refund --user-id=123

# Manually create wallet if missing
php artisan db:seed --class=WalletSeeder

# Check logs for errors
tail -f /var/www/laravel/storage/logs/laravel.log | grep -i "refund\|error"
```

### Issue: Scheduler not running (no logs in /var/log/laravel-schedule.log)

**Possible Causes:**
1. Cron service not running
2. Wrong cron entry
3. File permissions

**Fix:**
```bash
# 1. Check cron service
sudo systemctl status cron

# 2. Start if not running
sudo systemctl start cron
sudo systemctl enable cron

# 3. Verify crontab
crontab -l

# 4. Test manually
cd /var/www/laravel && php artisan schedule:run -v
```

### Issue: Lock timeout (previous process still running)

**Possible Causes:**
1. Command taking too long
2. Database deadlock
3. API timeout

**Fix:**
```bash
# Check running processes
ps aux | grep "activations:process-expired"

# Kill stuck process if needed
pkill -f "activations:process-expired"

# Try again
php artisan activations:process-expired -v
```

---

## ✅ Success Indicators

When auto-expiration is working correctly, you will see:

1. **Scheduler logs** showing command runs every 3 minutes:
   ```
   tail /var/log/laravel-schedule.log
   # Shows: Command "activations:process-expired" passed
   ```

2. **Application logs** showing refunds:
   ```
   tail /var/www/laravel/storage/logs/laravel.log | grep "REFUND SUCCESS"
   # Shows: ✓ REFUND SUCCESS - Reference: REF_A1B2C3D4E5 | Amount: ₦500.00
   ```

3. **Database records** showing refunds:
   ```sql
   SELECT * FROM transactions 
   WHERE type = 'credit' AND channel = 'Refund';
   # Shows refund transactions created
   ```

4. **Wallet balances** increasing:
   ```sql
   SELECT balance FROM wallets WHERE user_id = 123;
   # Shows balance increased after refund
   ```

---

## 🔧 Advanced: Manual Refund Testing

If automatic isn't working, test manually:

```bash
# 1. Create test activation (6 mins old)
php artisan activations:test-refund --create-test

# 2. Note the activation ID from output

# 3. Run auto-refund
php artisan activations:process-expired -v

# 4. Verify in database
php artisan activations:test-refund --activation-id=<ID>
```

---

## 📞 Support Resources

- **Provider1 (5sim):** https://5sim.net/en/api/documentation
- **Provider2 (SMS-Bus):** Your provider's API documentation
- **Laravel Scheduler:** https://laravel.com/docs/11.x/scheduling
- **Cron Syntax:** https://crontab.guru/

---

## Summary

**To make auto-expiration work:**

1. ✅ Set `FIVESIM_TOKEN` and `SMSBUS_TOKEN` in `.env`
2. ✅ Verify cron: `* * * * * cd /var/www/laravel && php artisan schedule:run`
3. ✅ Run: `php artisan activations:diagnose` to verify
4. ✅ Test with: `php artisan activations:test-refund --create-test`
5. ✅ Monitor with: `tail -f /var/log/laravel-schedule.log`

**Debug with:**
```bash
php artisan activations:diagnose
php artisan activations:test-refund --create-test
php artisan activations:process-expired -v
```
