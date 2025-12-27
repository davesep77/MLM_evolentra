@echo off
echo ========================================
echo Evolentra Telegram Bot - Easy Setup
echo ========================================
echo.

REM Set XAMPP PHP path
set PHP_PATH=d:\xampp\php\php.exe
set MYSQL_PATH=d:\xampp\mysql\bin\mysql.exe

echo Checking XAMPP installation...
if not exist "%PHP_PATH%" (
    echo [ERROR] PHP not found at: %PHP_PATH%
    echo Please update PHP_PATH in this script
    pause
    exit /b 1
)
echo [OK] PHP found: %PHP_PATH%
echo.

echo ========================================
echo STEP 1: Install Composer
echo ========================================
echo.
echo Please follow these steps:
echo 1. Download Composer from: https://getcomposer.org/Composer-Setup.exe
echo 2. Run the installer
echo 3. When asked for PHP path, use: %PHP_PATH%
echo 4. Complete the installation
echo.
echo Press any key when Composer is installed...
pause
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [WARNING] Composer not found in PATH
    echo Trying to use composer.phar...
    
    if not exist "composer.phar" (
        echo Downloading composer.phar...
        "%PHP_PATH%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        "%PHP_PATH%" composer-setup.php
        "%PHP_PATH%" -r "unlink('composer-setup.php');"
    )
    
    set COMPOSER_CMD=%PHP_PATH% composer.phar
) else (
    set COMPOSER_CMD=composer
)

echo ========================================
echo STEP 2: Install Dependencies
echo ========================================
echo.
echo Installing Telegram bot dependencies...
%COMPOSER_CMD% install
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Failed to install dependencies
    echo Trying alternative method...
    %COMPOSER_CMD% install --ignore-platform-reqs
)
echo [OK] Dependencies installed
echo.

echo ========================================
echo STEP 3: Update Database
echo ========================================
echo.
echo Choose your method:
echo 1. Automatic (MySQL command line)
echo 2. Manual (phpMyAdmin)
echo.
choice /C 12 /M "Select option"

if %ERRORLEVEL%==1 (
    echo Running database update...
    if exist "%MYSQL_PATH%" (
        "%MYSQL_PATH%" -u root mlm_system < setup_telegram_schema.sql
        if %ERRORLEVEL% NEQ 0 (
            echo [WARNING] Database update failed
            echo Please use phpMyAdmin instead
        ) else (
            echo [OK] Database updated
        )
    ) else (
        echo [ERROR] MySQL not found
        echo Please use phpMyAdmin
    )
)

if %ERRORLEVEL%==2 (
    echo.
    echo Manual steps:
    echo 1. Open: http://localhost/phpmyadmin
    echo 2. Select 'mlm_system' database
    echo 3. Click 'Import' tab
    echo 4. Choose file: setup_telegram_schema.sql
    echo 5. Click 'Go'
    echo.
    echo Press any key when database is updated...
    pause
)

echo.
echo ========================================
echo STEP 4: Create Telegram Bot
echo ========================================
echo.
echo Follow these steps in Telegram:
echo 1. Search for: @BotFather
echo 2. Send: /newbot
echo 3. Name: Evolentra Support Bot
echo 4. Username: evolentra_support_bot
echo 5. Copy the token (looks like: 123456789:ABC...)
echo.
echo Press any key when you have your bot token...
pause
echo.

set /p BOT_TOKEN="Paste your bot token here: "

echo.
echo Updating telegram_bot.php with your token...

REM Create a temporary PHP script to update the token
echo ^<?php > update_token.php
echo $file = 'telegram_bot.php'; >> update_token.php
echo $content = file_get_contents($file); >> update_token.php
echo $content = preg_replace( >> update_token.php
echo     "/'YOUR_BOT_TOKEN_HERE'/", >> update_token.php
echo     "'%BOT_TOKEN%'", >> update_token.php
echo     $content >> update_token.php
echo ); >> update_token.php
echo file_put_contents($file, $content); >> update_token.php
echo echo "Token updated successfully\n"; >> update_token.php
echo ?^> >> update_token.php

"%PHP_PATH%" update_token.php
del update_token.php

echo [OK] Bot token configured
echo.

echo ========================================
echo STEP 5: Start Bot
echo ========================================
echo.
echo Starting Telegram bot...
echo.
echo The bot will now start. Keep this window open!
echo.
echo To test:
echo 1. Find your bot in Telegram
echo 2. Send: /start
echo 3. Send your email to link account
echo 4. Try: /balance
echo.
echo Press Ctrl+C to stop the bot
echo.
pause

"%PHP_PATH%" telegram_bot.php
