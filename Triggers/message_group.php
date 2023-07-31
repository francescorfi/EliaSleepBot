<?php
require '../vendor/autoload.php';
require '../config.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

$chat_id = 'YOUR_GROUP_CHAT_ID';
$message_text = '¡Hola, grupo!';

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Define the data to be sent
    $data = [
        'chat_id' => $chat_id,
        'text'    => $message_text,
    ];

    // Send message to the group
    $result = Request::sendMessage($data);

    // Output success or error message
    if ($result->isOk()) {
        echo 'Message sent succesfully';
    } else {
        echo 'Message was not sent';
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Log telegram errors
    echo $e->getMessage();
}
