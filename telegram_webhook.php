<?php
/**
 * Telegram Webhook Handler
 * Set this as your webhook URL in Telegram
 */

require_once __DIR__ . '/config_db.php';
require_once __DIR__ . '/telegram_bot.php';

// Get bot token from environment
$BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN') ?: 'YOUR_BOT_TOKEN_HERE';

// Create bot instance
$bot = new EvolentraTelegramBot($BOT_TOKEN, $conn);

// Handle webhook
$bot->handleWebhook();

// Return 200 OK
http_response_code(200);
