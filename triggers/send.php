<?php
require '../vendor/autoload.php';
require '../config.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;
use mysqli;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Get message id from URL
    $message_id = $_GET['id'] ?? null;

    if ($message_id !== null) {
        // Connect to the database
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Query the database for the chat_id and message_text
        $stmt = $mysqli->prepare("SELECT chat_id, message_text FROM messages WHERE id = ?");
        $stmt->bind_param('s', $message_id);
        $stmt->execute();
        $stmt->bind_result($chat_id, $message_text);
        $stmt->fetch();
        $stmt->close();
        $mysqli->close();

        if ($chat_id && $message_text) {
            // Define the data to be sent
            $data = [
                'chat_id' => $chat_id,
                'text'    => $message_text,
            ];

            // Send message to the group
            $result = Request::sendMessage($data);

            // Output success or error message
            if ($result->isOk()) {
                TelegramLog::notice("Message with ID $message_id sent successfully");
            } else {
                TelegramLog::notice("Message with ID $message_id was not sent");
            }
        } else {
            TelegramLog::notice("Message with ID $message_id does not exist in the database");
        }
    } else {
        TelegramLog::notice("No message ID provided");
    }
} catch (Exception $e) {
    // Log telegram errors
    TelegramLog::error($e);
}

// Always respond with OK
http_response_code(200);
