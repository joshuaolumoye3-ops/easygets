# VPS Production Deployment Guide - OTP Scheduler

Instead of manually running `php artisan otp:check-pending-orders` on your VPS, use one of these production solutions:

---

## ✅ **OPTION 1: CRON JOB (Recommended - Easiest)**

### Setup Cron Job on VPS

Connect to your VPS via SSH and add a cron job:

```bash
# Open crontab editor
crontab -e

# Add this line (it will run every minute):
* * * * * cd /var/www/easygets.ng && php artisan schedule:run >> /dev/null 2>&1

# Or run the OTP checker directly every minute:
* * * * * cd /var/www/easygets.ng && php artisan otp:check-pending-orders >> /var/log/easygets-otp.log 2>&1
```

### Verify Cron Job is Running

```bash
# List all cron jobs
crontab -l

# Check logs
tail -f /var/log/easygets-otp.log
```

**What You'll See in Logs:**
```
2026-03-27 11:00:01 ✓ [ID: 58] SMS Code received: 583087
2026-03-27 11:01:01 ○ [ID: 47] Still waiting... Status: RECEIVED
2026-03-27 11:02:01 ✓ [ID: 59] SMS Code received: 337694
```

---

## ✅ **OPTION 2: SUPERVISOR (For High Traffic)**

### Install Supervisor

```bash
sudo apt-get install supervisor
```

### Create Config File

```bash
sudo nano /etc/supervisor/conf.d/easygets-otp.conf
```

**Add This Content:**
```ini
[program:easygets-otp-checker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/easygets.ng/artisan otp:check-pending-orders
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/easygets-otp-supervisor.log
user=www-data
stopasgroup=true
killasgroup=true
```

### Start Supervisor

```bash
# Reread and update configs
sudo supervisorctl reread
sudo supervisorctl update

# Check status
sudo supervisorctl status

# Start the process
sudo supervisorctl start easygets-otp-checker:*

# View logs
tail -f /var/log/easygets-otp-supervisor.log
```

---

## ✅ **OPTION 3: LARAVEL SCHEDULER (Built-in - Use With Cron)**

Your `app/Console/Kernel.php` already has:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('otp:check-pending-orders')->everyMinute();
}
```

### Setup Cron to Call Scheduler

```bash
# Add to crontab:
* * * * * cd /var/www/easygets.ng && php artisan schedule:run >> /dev/null 2>&1
```

**Advantages:**
- More scalable (can add other scheduled tasks)
- Built into Laravel
- Easier to manage multiple tasks

---

## 🔍 **How It Works Behind the Scenes**

### Frontend (User's Browser)
```
1. Page loads → Auto-polling starts every 3 seconds
2. Polling: GET /app/history/get-sms
3. If SMS found → Display code with animation
4. Stop polling when code received ✓
```

### Backend (VPS - Cron/Supervisor)
```
1. Every minute (or configured interval)
2. Command checks 5sim API for pending orders
3. If SMS received → Save to database
4. Log the action (success/pending/expired)
5. Next minute → Check again
```

### Database
```
Transactions Table:
├── id: 58
├── request_id: 11631253 (5sim order ID)
├── status: pending → completed (when SMS arrives)
├── sms_code: null → 583087 (when saved)
└── updated_at: 2026-03-27 11:00:01
```

---

## 📊 **Flow Diagram**

```
User (History Page)
    ↓
Browser polls every 3 sec
    ↓
GET /app/history/get-sms
    ↓
HistoryController→getSms()
    ↓
Check DB for sms_code
    ├─ If found → Return code + display ✓
    └─ If not → Keep polling
    
=====================================

Backend (VPS - Every Minute)
    ↓
Cron/Supervisor runs
    ↓
php artisan otp:check-pending-orders
    ↓
CheckPendingOtpOrders command
    ↓
Loop all pending orders
    ├─ Call 5sim API: https://5sim.net/v1/user/check/{id}
    ├─ Parse response for SMS
    ├─ If SMS found → Save sms_code + status=completed
    └─ Log result
    
=====================================

Result: User sees code without refresh! 🎉
```

---

## ⚙️ **Troubleshooting**

### Codes Not Appearing?

**Check 1: Is Cron Running?**
```bash
# View cron logs
grep CRON /var/log/syslog | tail -20

# Or check if command runs manually
cd /var/www/easygets.ng && php artisan otp:check-pending-orders
```

**Check 2: Database Permission**
```bash
# Check if app can write to DB
php artisan tinker
# Type: \App\Models\Transaction::where('id', 1)->first();
```

**Check 3: 5sim API Token Valid?**
```bash
# Check .env file
cat /var/www/easygets.ng/.env | grep FIVESIM_TOKEN

# Test token manually:
curl -H "Authorization: Bearer YOUR_TOKEN" https://5sim.net/v1/user/check/11631253
```

**Check 4: Logs**
```bash
# Laravel logs
tail -f /var/www/easygets.ng/storage/logs/laravel.log

# If cron log file exists:
tail -f /var/log/easygets-otp.log
```

---

## 🚀 **Quick Start for VPS**

```bash
# 1. SSH into VPS
ssh user@your-vps-ip

# 2. Add cron job
crontab -e
# Add: * * * * * cd /var/www/easygets.ng && php artisan schedule:run >> /dev/null 2>&1

# 3. Verify it's working
crontab -l

# 4. Monitor for first 5 minutes
watch -n 1 'tail -20 /var/log/easygets-otp.log'

# 5. Done! Users will see codes auto-update 🎉
```

---

## 📝 **Local Development Testing**

```bash
# Test on local machine with schedule:work
php artisan schedule:work

# This simulates the VPS cron job running every minute
# You'll see output like:
# "otp:check-pending-orders ........................ Running [completed]"
```

---

## 🎯 **Summary**

| Method | Setup Time | Reliability | CPU Usage | Recommended |
|--------|-----------|-------------|-----------|-------------|
| Cron Job | 2 min | ⭐⭐⭐⭐⭐ | Low | ✅ **Yes** |
| Supervisor | 10 min | ⭐⭐⭐⭐⭐ | Medium | For high traffic |
| Manual CLI | ∞ | ❌ | - | ❌ No |

**For your setup: Use CRON JOB (Option 1)** - It's simple, reliable, and perfect for 1-minute intervals.
