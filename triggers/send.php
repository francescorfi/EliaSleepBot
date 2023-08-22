<?php
require '../vendor/autoload.php';
require '../config.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Get message id from URL
    $send_id = $_GET['id'] ?? null;

    if ($send_id !== null) {
        // Connect to the database
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Query the database for the chat_id and command
        $stmt = $mysqli->prepare("SELECT s.chat_id, m.command, m.id as message_id FROM sends s INNER JOIN messages m ON s.message_id = m.id WHERE s.id = ?");
        $stmt->bind_param('s', $send_id);
        $stmt->execute();
        $stmt->bind_result($chat_id, $command, $message_id);
        $stmt->fetch();
        $stmt->close();

        if ($chat_id && $command) {
            // Define the data to be sent
            $data = [
                'chat_id' => $chat_id,
                'text'    => $command,
            ];

            // Send message to the group
            $result = Request::sendMessage($data);

            if ($result->isOk()) {
                TelegramLog::notice("Message with ID $send_id sent successfully");

                // Insert event into the events table
                $eventStmt = $mysqli->prepare("INSERT INTO events (chat_id, message_id) VALUES (?, ?)");
                $eventStmt->bind_param('ss', $chat_id, $message_id);
                $eventStmt->execute();
                $eventStmt->close();
                
            } else {
                TelegramLog::notice("Message with ID $send_id was not sent");
            }
        } else {
            TelegramLog::notice("Message with ID $send_id does not exist in the database");
        }

        $mysqli->close();
    } else {
        TelegramLog::notice("No send ID provided");
    }
} catch (Exception $e) {
    // Log telegram errors
    TelegramLog::error($e);
}

// Always respond with OK
http_response_code(200);
