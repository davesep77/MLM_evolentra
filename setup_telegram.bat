@echo off
echo ========================================
echo Evolentra Telegram Bot Setup
echo ========================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Composer not found!
    echo Please install Composer from: https://getcomposer.org/download/
    pause
    exit /b 1
)

echo [1/5] Installing dependencies...
composer install
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Composer install failed!
    pause
    exit /b 1
)
echo [OK] Dependencies installed
echo.

echo [2/5] Updating database schema...
mysql -u root mlm_system < setup_telegram_schema.sql
if %ERRORLEVEL% NEQ 0 (
    echo [WARNING] Database update failed. Please run manually:
    echo mysql -u root mlm_system < setup_telegram_schema.sql
) else (
    echo [OK] Database updated
)
echo.

echo [3/5] Checking configuration...
if not exist ".env" (
    echo Creating .env file...
    copy .env.example .env
)
echo.

echo ========================================
echo MANUAL STEPS REQUIRED:
echo ========================================
echo.
echo 1. Create bot with @BotFather on Telegram
echo    - Send: /newbot
echo    - Follow instructions
echo    - Copy the bot token
echo.
echo 2. Set your bot token:
echo    Option A: Edit .env file and add:
echo              TELEGRAM_BOT_TOKEN=your_token_here
echo.
echo    Option B: Run this command:
echo              set TELEGRAM_BOT_TOKEN=your_token_here
echo.
echo 3. Start the bot:
echo              php telegram_bot.php
echo.
echo 4. Test in Telegram:
echo    - Find your bot
echo    - Send: /start
echo    - Send your email to link account
echo.
echo ========================================
echo Setup script completed!
echo ========================================
pause
