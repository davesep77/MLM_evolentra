#!/bin/bash

echo "========================================"
echo "Evolentra Telegram Bot Setup"
echo "========================================"
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "[ERROR] Composer not found!"
    echo "Please install Composer from: https://getcomposer.org/download/"
    exit 1
fi

echo "[1/5] Installing dependencies..."
composer install
if [ $? -ne 0 ]; then
    echo "[ERROR] Composer install failed!"
    exit 1
fi
echo "[OK] Dependencies installed"
echo ""

echo "[2/5] Updating database schema..."
mysql -u root mlm_system < setup_telegram_schema.sql
if [ $? -ne 0 ]; then
    echo "[WARNING] Database update failed. Please run manually:"
    echo "mysql -u root mlm_system < setup_telegram_schema.sql"
else
    echo "[OK] Database updated"
fi
echo ""

echo "[3/5] Checking configuration..."
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
fi
echo ""

echo "========================================"
echo "MANUAL STEPS REQUIRED:"
echo "========================================"
echo ""
echo "1. Create bot with @BotFather on Telegram"
echo "   - Send: /newbot"
echo "   - Follow instructions"
echo "   - Copy the bot token"
echo ""
echo "2. Set your bot token:"
echo "   Option A: Edit .env file and add:"
echo "             TELEGRAM_BOT_TOKEN=your_token_here"
echo ""
echo "   Option B: Run this command:"
echo "             export TELEGRAM_BOT_TOKEN=your_token_here"
echo ""
echo "3. Start the bot:"
echo "             php telegram_bot.php"
echo ""
echo "4. Test in Telegram:"
echo "   - Find your bot"
echo "   - Send: /start"
echo "   - Send your email to link account"
echo ""
echo "========================================"
echo "Setup script completed!"
echo "========================================"
