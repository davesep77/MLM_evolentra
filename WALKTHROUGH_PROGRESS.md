# 🎯 STEP-BY-STEP WALKTHROUGH

I'm walking you through the Telegram bot setup right now!

---

## 📍 WHERE WE ARE

✅ I've started the setup script for you
✅ Opening phpMyAdmin in your browser
⏳ Waiting for you to complete each step

---

## 🔴 STEP 1: UPDATE DATABASE (You're here!)

### What's happening:
- phpMyAdmin should open in your browser
- If not, go to: http://localhost/phpmyadmin

### What you need to do:

1. **In phpMyAdmin, click on `mlm_system`** (left sidebar)

2. **Click the "SQL" tab** (top menu)

3. **Copy this SQL and paste it:**

```sql
-- Add Telegram fields to mlm_users table
ALTER TABLE mlm_users 
ADD COLUMN telegram_id VARCHAR(50) DEFAULT NULL,
ADD COLUMN telegram_username VARCHAR(100) DEFAULT NULL,
ADD COLUMN telegram_notifications TINYINT(1) DEFAULT 1;

-- Create index for faster lookups
ALTER TABLE mlm_users 
ADD UNIQUE INDEX idx_telegram_id (telegram_id);
```

4. **Click the "Go" button** (bottom right)

5. **Wait for success message** (should say "Query OK")

### ✅ When done, tell me: "Database updated"

---

## 🟡 STEP 2: CREATE TELEGRAM BOT (Next)

I'll guide you through this after Step 1 is complete.

---

## 🟢 STEP 3: START THE BOT (Final step)

We'll do this together after Step 2.

---

**Current Status**: Waiting for you to update the database in phpMyAdmin.

Let me know when you see the success message!
