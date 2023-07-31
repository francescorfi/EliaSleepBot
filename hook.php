<?php
require 'vendor/autoload.php';
require 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Commands\SystemCommands\NewMessageCommand;
use Longman\TelegramBot\Commands\SystemCommands\EditMessageCommand;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($settings['commands_paths']);   

    // Handle telegram webhook request
    $telegram->handle();

    // Manejo de callback queries
    if ($result instanceof CallbackQuery) {
        $callback_query = $result;
        $callback_data = $callback_query->getData();

        // Manejo de los comandos configurados en el teclado inline
        switch ($callback_data) {
            case 'command_new_message':
                $telegram->executeCommand('newmessage');
                break;
            case 'command_edit_message':
                $telegram->executeCommand('editmessage');
                break;
            default:
                $telegram->executeCommand('callbackquery');
                break;
        }
    }

} catch (TelegramException $e) {
    // Log telegram errors
    TelegramLog::error($e);
}
