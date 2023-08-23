<?php
require '../vendor/autoload.php';
require '../config.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

try {
    // Create Telegram API object
    $telegram = new Telegram($settings['api_key'], $settings['bot_username']);

    // Get chat id from URL
    $chat_id = $_GET['id'] ?? null;

    if ($chat_id !== null) {
        // Connect to the database
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Query the database for the latest analysis for the given chat_id
        $stmt = $mysqli->prepare("SELECT * FROM analysis WHERE chat_id = ? ORDER BY analysis_date DESC LIMIT 1");
        $stmt->bind_param('s', $chat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analysis = $result->fetch_assoc();
        $stmt->close();

        if ($analysis) {
            // Create the summary message
            $message = "Resumen del análisis (Fecha: {$analysis['analysis_date']}):\n";
            $message .= "Siestas durante el día: {$analysis['day_naps']}\n";
            $message .= "Horas totales dormidas durante el día: {$analysis['total_hours_slept_day']}\n";
            // ... add more fields here

            $data = [
                'chat_id' => $chat_id,
                'text'    => $message,
            ];

            // Send summary message to the group
            $result = Request::sendMessage($data);

            if ($result->isOk()) {
                TelegramLog::notice("Summary for chat ID $chat_id sent successfully");
            } else {
                TelegramLog::notice("Summary for chat ID $chat_id was not sent");
            }
        } else {
            TelegramLog::notice("No analysis data found for chat ID $chat_id");
        }

        $mysqli->close();
    } else {
        TelegramLog::notice("No chat ID provided");
    }
} catch (Exception $e) {
    // Log telegram errors
    TelegramLog::error($e);
}

// Always respond with OK
http_response_code(200);
