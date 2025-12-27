@echo off
color 0A
cls
echo.
echo  ========================================
echo   STARTING EVOLENTRA TELEGRAM BOT
echo  ========================================
echo.
echo  Your bot is now starting...
echo.
echo  Bot Token: 8508685878:AAEowOz...
echo  Status: ACTIVE
echo.
echo  ========================================
echo   HOW TO TEST YOUR BOT
echo  ========================================
echo.
echo  1. Open Telegram on your phone/computer
echo  2. Search for your bot (the username you chose)
echo  3. Click START
echo  4. Send your email to link your account
echo  5. Try these commands:
echo     /balance - Check your wallet balances
echo     /stats - View your account statistics
echo     /referral - Get your referral link
echo     /help - See all commands
echo.
echo  ========================================
echo   KEEP THIS WINDOW OPEN!
echo  ========================================
echo.
echo  The bot will stop if you close this window.
echo  Press Ctrl+C to stop the bot.
echo.
echo  Starting in 3 seconds...
timeout /t 3 /nobreak >nul

cls
echo.
echo  ========================================
echo   BOT IS RUNNING!
echo  ========================================
echo.
echo  Waiting for messages...
echo.

d:\xampp\php\php.exe telegram_bot_simple.php
