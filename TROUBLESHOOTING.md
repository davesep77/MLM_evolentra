# Telegram Bot Setup - Troubleshooting Guide

## 🔍 Current Issues Detected

### Issue 1: PHP Not in System PATH ❌
**Problem**: The command `php` is not recognized

**Solution**: Use XAMPP's PHP directly
```cmd
d:\xampp\php\php.exe --version
```

### Issue 2: Composer Not Installed ⏳
**Status**: Waiting for installation

---

## 🚀 Quick Fix - Use the Easy Setup Script

I've created a new setup script that handles everything automatically!

### Run This Instead:
```cmd
cd d:\xampp\htdocs\MLM_Evolentra
setup_telegram_easy.bat
```

This script will:
1. ✅ Use XAMPP's PHP automatically
2. ✅ Guide you through Composer installation
3. ✅ Install dependencies
4. ✅ Update database
5. ✅ Help you create the bot
6. ✅ Configure the token
7. ✅ Start the bot

---

## 📋 Manual Steps (If You Prefer)

### Step 1: Install Composer

**Download**: https://getcomposer.org/Composer-Setup.exe

**During Installation**:
- When asked for PHP path, enter: `d:\xampp\php\php.exe`
- Complete the wizard
- Close and reopen terminal

### Step 2: Install Dependencies

```cmd
cd d:\xampp\htdocs\MLM_Evolentra
composer install
```

**If that fails**, try:
```cmd
d:\xampp\php\php.exe composer.phar install
```

### Step 3: Update Database

**Option A - phpMyAdmin (Easiest)**:
1. Go to: http://localhost/phpmyadmin
2. Click `mlm_system` database
3. Click "Import" tab
4. Choose: `setup_telegram_schema.sql`
5. Click "Go"

**Option B - Command Line**:
```cmd
d:\xampp\mysql\bin\mysql -u root mlm_system < setup_telegram_schema.sql
```

### Step 4: Create Bot on Telegram

1. Open Telegram
2. Search: `@BotFather`
3. Send: `/newbot`
4. Name: `Evolentra Support Bot`
5. Username: `evolentra_support_bot`
6. **Copy the token!**

### Step 5: Configure Token

Edit `telegram_bot.php` line 15:
```php
$BOT_TOKEN = 'your_token_here';
```

### Step 6: Start Bot

```cmd
d:\xampp\php\php.exe telegram_bot.php
```

---

## 🆘 Common Problems & Solutions

### Problem: "Composer not found"
**Solution 1**: Install from https://getcomposer.org/Composer-Setup.exe

**Solution 2**: Download composer.phar manually:
```cmd
cd d:\xampp\htdocs\MLM_Evolentra
d:\xampp\php\php.exe -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
d:\xampp\php\php.exe composer-setup.php
d:\xampp\php\php.exe composer.phar install
```

### Problem: "PHP not found"
**Solution**: Always use full path:
```cmd
d:\xampp\php\php.exe
```

Or add to PATH:
1. Search "Environment Variables" in Windows
2. Edit "Path" variable
3. Add: `d:\xampp\php`
4. Restart terminal

### Problem: Database import failed
**Solution**: Use phpMyAdmin instead:
- http://localhost/phpmyadmin
- Import the SQL file manually

### Problem: Bot not responding
**Check**:
1. Token is correct (no spaces)
2. Bot script is running
3. No errors in terminal
4. Try `/start` command again

### Problem: Can't link account
**Check**:
1. Email exists in database
2. Using exact email from registration
3. `telegram_id` column exists in `mlm_users` table

---

## ✅ Verification Checklist

Run these commands to verify everything:

### Check PHP:
```cmd
d:\xampp\php\php.exe --version
```
Expected: `PHP 7.x or 8.x`

### Check MySQL:
```cmd
d:\xampp\mysql\bin\mysql --version
```
Expected: `mysql Ver 15.x`

### Check Composer (after installation):
```cmd
composer --version
```
Expected: `Composer version 2.x`

### Check Database:
```sql
USE mlm_system;
SHOW COLUMNS FROM mlm_users LIKE 'telegram_id';
```
Expected: Should show the column

### Check Bot File:
```cmd
type telegram_bot.php | findstr "BOT_TOKEN"
```
Expected: Should show your token (not YOUR_BOT_TOKEN_HERE)

---

## 🎯 Recommended Approach

**For Beginners**: Use `setup_telegram_easy.bat`
- It handles everything step-by-step
- Guides you through each step
- Uses correct XAMPP paths

**For Advanced Users**: Follow manual steps
- More control over the process
- Can troubleshoot issues easier
- Understand each component

---

## 📞 Still Need Help?

### Quick Diagnostics

Run this to check your system:
```cmd
echo PHP: & d:\xampp\php\php.exe --version
echo.
echo MySQL: & d:\xampp\mysql\bin\mysql --version
echo.
echo Composer: & composer --version
```

### Send Me This Info:
1. Output of diagnostics above
2. Any error messages you see
3. Which step you're stuck on

I'll provide specific solutions!

---

## 🚀 Next: Run Easy Setup

Close the stuck setup script and run:
```cmd
setup_telegram_easy.bat
```

This will guide you through everything with proper XAMPP paths!
