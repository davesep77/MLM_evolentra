@echo off
color 0A
title Evolentra Telegram Bot - Quick Setup
cls

echo.
echo  ========================================
echo   EVOLENTRA TELEGRAM BOT - QUICK SETUP
echo  ========================================
echo.
echo  This will set up your Telegram bot in 3 easy steps!
echo.
pause

cls
echo.
echo  ========================================
echo   STEP 1: UPDATE DATABASE
echo  ========================================
echo.
echo  Opening phpMyAdmin in your browser...
echo.
start http://localhost/phpmyadmin
echo.
echo  In phpMyAdmin:
echo  1. Click 'mlm_system' database (left side)
echo  2. Click 'SQL' tab (top menu)
echo  3. Copy and paste this SQL:
echo.
echo  ----------------------------------------
type setup_telegram_schema.sql
echo  ----------------------------------------
echo.
echo  4. Click 'Go' button
echo  5. Wait for success message
echo.
pause

cls
echo.
echo  ========================================
echo   STEP 2: CREATE TELEGRAM BOT
echo  ========================================
echo.
echo  1. Open Telegram on your phone/computer
echo  2. Search for: @BotFather
echo  3. Send this message: /newbot
echo  4. When asked for name, send: Evolentra Support Bot
echo  5. When asked for username, send: evolentra_support_bot
echo  6. BotFather will give you a TOKEN
echo  7. Copy the entire token
echo.
echo  The token looks like:
echo  123456789:ABCdefGHIjklMNOpqrsTUVwxyz
echo.
pause

cls
echo.
echo  ========================================
echo   STEP 3: CONFIGURE BOT TOKEN
echo  ========================================
echo.
set /p TOKEN="Paste your bot token here and press Enter: "

echo.
echo  Saving your token...

REM Update the bot file with the token
powershell -Command "(Get-Content telegram_bot.php) -replace 'YOUR_BOT_TOKEN_HERE', '%TOKEN%' | Set-Content telegram_bot.php"

echo  [OK] Token saved!
echo.
pause

cls
echo.
echo  ========================================
echo   STARTING YOUR BOT...
echo  ========================================
echo.
echo  Your bot is now starting!
echo.
echo  To test it:
echo  1. Open Telegram
echo  2. Search for your bot: @evolentra_support_bot
echo  3. Click START
echo  4. Send your email to link your account
echo  5. Try commands: /balance /stats /help
echo.
echo  Keep this window open while the bot runs!
echo  Press Ctrl+C to stop the bot.
echo.
pause

d:\xampp\php\php.exe telegram_bot.php
