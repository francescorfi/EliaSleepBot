<?php
require 'vendor/autoload.php';
require 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Exception\TelegramException;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Set webhook
    $result = $telegram->setWebhook($settings['hook_url']);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (TelegramException $e) {
    // Log telegram errors
    TelegramLog::error($e);
}
