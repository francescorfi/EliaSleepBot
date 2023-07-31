<?php
require 'vendor/autoload.php';
require 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;

use Commands\MessageCommand;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($settings['commands_paths']);   

    // Handle telegram webhook request
    $telegram->handle();

    // Get the update object
    $update = $telegram->getWebhookUpdate();

    // Check if the update contains a text message
    if ($update->getMessage() && !$update->getMessage()->isCommand()) {
        // Instantiate and execute the MessageCommand
        $command = new MessageCommand($telegram, $update);
        $command->preExecute();
        $command->execute();
    }

} catch (TelegramException $e) {
    // Log telegram errors
    TelegramLog::error($e);
}
