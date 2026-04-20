# Bot Prevention & User Blocking Implementation Guide

## Overview
This guide implements comprehensive bot prevention measures and user blocking functionality for your EasyGets platform.

---

## Part 1: Database Changes

Run these MongoDB/MySQL commands on your VPS to add the required columns:

### MySQL Commands

```sql
-- Add is_blocked, blocked_reason, blocked_at, and last_login_at columns to users table
ALTER TABLE users ADD COLUMN is_blocked BOOLEAN DEFAULT FALSE AFTER email_verified_at;
ALTER TABLE users ADD COLUMN blocked_reason TEXT NULL AFTER is_blocked;
ALTER TABLE users ADD COLUMN blocked_at TIMESTAMP NULL AFTER blocked_reason;
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL AFTER blocked_at;

-- Create indexes for better query performance
CREATE INDEX idx_users_is_blocked ON users(is_blocked);
CREATE INDEX idx_users_blocked_at ON users(blocked_at);
CREATE INDEX idx_users_last_login ON users(last_login_at);

-- Verify the columns were added successfully
DESCRIBE users;
```

**Expected Output**: You should see the new columns in the users table with the correct data types and defaults.

---

## Part 2: Code Changes Already Applied

The following changes have been implemented in your codebase:

### 1. **Enhanced Registration Controller** (`app/Http/Controllers/Auth/RegisterController.php`)
   - Rate limiting: Max 5 registration attempts per IP per hour
   - Honeypot field detection
   - Disposable email blocking (15+ domains)
   - Suspicious User-Agent detection
   - Username pattern validation (prevents common bot usernames)
   - Email pattern validation
   - Comprehensive logging of suspicious activities
   - Minimum password requirements (8 chars with numbers & special chars)

### 2. **Updated User Model** (`app/Models/User.php`)
   - Added `is_blocked`, `blocked_reason`, `blocked_at` to fillable array
   - Added scopes: `active()` and `blocked()`
   - Added methods: `isBlocked()`, `block()`, `unblock()`
   - Updated casts for boolean and datetime fields

### 3. **Enhanced Login Controller** (`app/Http/Controllers/Auth/LoginController.php`)
   - Checks if user is blocked before allowing login
   - Logs all blocked user login attempts
   - Updates `last_login_at` timestamp on successful login
   - Clear error message when account is blocked

### 4. **Admin Controller Updates** (`app/Http/Controllers/AdminController.php`)
   - New method: `blockUser()` - Block a user with reason
   - New method: `unblockUser()` - Unblock a previously blocked user
   - New method: `getUserBlockStatus()` - Get user's blocking status
   - New private method: `invalidateUserSessions()` - Force logout on block
   - All methods are logged to admin transaction audit trail

### 5. **Blocked User Middleware** (`app/Http/Middleware/CheckUserNotBlocked.php`)
   - Checks if authenticated user is blocked
   - Forces logout if user is blocked
   - Prevents any operations by blocked users
   - Comprehensive logging of blocked user access attempts

### 6. **Registration Form** (`resources/views/onboarding/signup.blade.php`)
   - Added honeypot field (hidden from real users)
   - This traps automated bots that fill in all form fields

### 7. **Routes** (`routes/web.php`)
   - Added new admin routes:
     - `POST /admin/users/{id}/block` - Block a user
     - `POST /admin/users/{id}/unblock` - Unblock a user
     - `GET /admin/users/{id}/block-status` - Get block status
   - Applied `check.user.not.blocked` middleware to all authenticated routes

### 8. **Bootstrap Configuration** (`bootstrap/app.php`)
   - Registered `CheckUserNotBlocked` middleware as `check.user.not.blocked`

---

## Part 3: Running the Migration

After deploying the code, run the migration on your VPS:

```bash
# SSH into your VPS
ssh user@your-vps-ip

# Navigate to your project directory
cd /path/to/your/project

# Run the migration
php artisan migrate

# Verify the migration ran successfully
php artisan migrate:status
```

---

## Part 4: How to Block/Unblock Users from Admin Dashboard

### Via API Endpoints:

**Block a User:**
```bash
POST /admin/users/{userId}/block
Content-Type: application/json
Authorization: Bearer {admin_token}

{
    "reason": "Suspicious bot activity detected"
}
```

**Unblock a User:**
```bash
POST /admin/users/{userId}/unblock
Content-Type: application/json
Authorization: Bearer {admin_token}
```

**Get Block Status:**
```bash
GET /admin/users/{userId}/block-status
Authorization: Bearer {admin_token}
```

### Response Examples:

**Block Success:**
```json
{
    "success": true,
    "message": "User username has been successfully blocked.",
    "is_blocked": true
}
```

**Unblock Success:**
```json
{
    "success": true,
    "message": "User username has been successfully unblocked.",
    "is_blocked": false
}
```

---

## Part 5: Bot Prevention Measures Implemented

### 1. **Rate Limiting**
   - Maximum 5 registration attempts per IP address per hour
   - Additional 10-second delay between rapid submissions

### 2. **Honeypot Field**
   - Hidden field `website_url` that legitimate users won't see
   - Bots that fill in all form fields will be caught
   - No error shown to user (security principle)

### 3. **Disposable Email Blocking**
   - Blocks common temporary email services:
     - tempmail.com, 10minutemail.com, guerrillamail.com, mailinator.com
     - throwaway.email, sharklasers.com, yopmail.com, and others
   - Prevents use of throwaway emails for bot testing

### 4. **Suspicious Username Patterns**
   - Blocks usernames matching bot patterns:
     - admin, test, bot, fake, dummy, user123
     - Simple patterns like `abc12345`
     - CIS patterns like `xd123456`, `qq12345`

### 5. **Email Pattern Validation**
   - Rejects emails with 5+ consecutive numbers at start
   - Rejects emails with 4+ repeated characters
   - Rejects emails with 3+ dots

### 6. **User-Agent Validation**
   - Rejects requests from:
     - Bots, crawlers, spiders, scrapers
     - API tools (curl, wget, Python, Java, etc.)
     - Testing tools (Postman, Insomnia, Thunder Client)
   - Rejects empty or suspiciously short User-Agents

### 7. **Password Requirements**
   - Minimum 8 characters
   - Must contain letters, numbers, and special characters
   - Prevents weak password attacks

### 8. **Audit Logging**
   - All suspicious registration attempts are logged
   - All block/unblock actions are logged with admin ID
   - Failed login attempts by blocked users are logged

---

## Part 6: Manual Database Cleanup (If Needed)

If you want to delete suspected bot accounts:

```sql
-- View recently created bot accounts (created in last 24 hours)
SELECT id, username, email, created_at FROM users 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;

-- Soft delete approach: Block them instead of deleting
UPDATE users 
SET is_blocked = TRUE, 
    blocked_reason = 'Suspected bot account',
    blocked_at = NOW()
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND is_blocked = FALSE;

-- Hard delete approach (only if necessary - use with caution!)
-- DELETE FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND id NOT IN (SELECT DISTINCT user_id FROM transactions);
```

---

## Part 7: Monitoring Bot Activity

### Check Recent Registration Attempts:
```sql
-- Recent registrations
SELECT id, username, email, created_at, is_blocked 
FROM users 
ORDER BY created_at DESC 
LIMIT 50;

-- Count registrations per hour (last 24 hours)
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
    COUNT(*) as registration_count
FROM users 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
ORDER BY hour DESC;

-- Blocked users
SELECT id, username, email, blocked_at, blocked_reason 
FROM users 
WHERE is_blocked = TRUE 
ORDER BY blocked_at DESC;
```

---

## Part 8: Testing the Implementation

### Test Bot Prevention:

1. **Rate Limiting Test:**
   - Try registering 6 times from the same IP within 1 hour
   - Should get "Too many registration attempts" error after 5th attempt

2. **Honeypot Test:**
   - Use browser developer tools to make honeypot field NOT hidden
   - Fill it in with a value and try to register
   - Should get "Invalid form submission" error

3. **Disposable Email Test:**
   - Try registering with `test@tempmail.com`
   - Should get "Disposable emails are not allowed" error

4. **Bot Username Test:**
   - Try registering with username `bot12345` or `test123`
   - Should get "This username is not allowed" error

5. **Login with Blocked User Test:**
   - Block a user from admin panel
   - Try logging in with that user
   - Should get "Your account has been blocked" message

### Test User Blocking:

1. Create a test user account
2. Go to admin dashboard
3. Find the user modal and click "Block User"
4. Enter a reason (e.g., "Testing block functionality")
5. Click confirm
6. Try logging in with that user - should be blocked
7. Go back to admin and "Unblock User"
8. Now the user should be able to login again

---

## Part 9: Troubleshooting

### Issue: "Too many registration attempts" error too aggressive

**Solution:** Modify the rate limiter in `RegisterController.php`:
```php
// Change from 5 to your desired limit
if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
    // ... error message
}
```

### Issue: Legitimate users getting blocked due to suspicious patterns

**Solution:** Adjust patterns in `RegisterController.php`:
```php
private const BOT_USERNAME_PATTERNS = [
    // Modify or remove patterns that are too strict
];
```

### Issue: Blocked users still can access some pages

**Solution:** Ensure all authenticated routes have `check.user.not.blocked` middleware applied.

---

## Part 10: Next Steps

1. ✅ Deploy the code changes to your VPS
2. ✅ Run the migration: `php artisan migrate`
3. ✅ Test the bot prevention measures
4. ✅ Test user blocking functionality
5. ✅ Monitor for bot registrations in logs
6. ✅ Use admin dashboard to block suspicious accounts

---

## Security Best Practices

1. **Regularly monitor** user registration logs for patterns
2. **Keep disposable email list updated** with new temporary email services
3. **Track blocked accounts** and investigate why they were blocked
4. **Review admin actions** - all block/unblock actions are audited
5. **Consider adding CAPTCHA** in the future for additional protection
6. **Implement IP geolocation** checking if you notice regional bot patterns
7. **Monitor API rate limits** to catch API-based registration attempts
8. **Use WAF (Web Application Firewall)** on your VPS for additional DDoS/bot protection

---

## Files Modified

- ✅ `app/Http/Controllers/Auth/RegisterController.php` - Bot prevention logic
- ✅ `app/Models/User.php` - User model with blocking methods
- ✅ `app/Http/Controllers/Auth/LoginController.php` - Login validation
- ✅ `app/Http/Controllers/AdminController.php` - Admin blocking methods
- ✅ `app/Http/Middleware/CheckUserNotBlocked.php` - Blocked user middleware (NEW)
- ✅ `bootstrap/app.php` - Middleware registration
- ✅ `routes/web.php` - New admin routes
- ✅ `resources/views/onboarding/signup.blade.php` - Honeypot field
- ✅ `database/migrations/2026_04_20_000000_add_is_blocked_to_users_table.php` - Migration (NEW)

---

## Support & Questions

If you encounter any issues:
1. Check the application logs: `/storage/logs/laravel-{date}.log`
2. Check admin transaction audit trail for blocked user history
3. Verify all migrations ran successfully: `php artisan migrate:status`
4. Ensure middleware is properly registered in `bootstrap/app.php`

Good luck! Your platform now has enterprise-level bot protection! 🚀
