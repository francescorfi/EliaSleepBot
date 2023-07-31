<?php
require 'vendor/autoload.php';
require 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Exception\TelegramException;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($settings['commands_paths']);   

    // Handle telegram webhook request
    $telegram->handle();

} catch (TelegramException $e) {
    // Log telegram errors
    TelegramLog::error($e);
}
