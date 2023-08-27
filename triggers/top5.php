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

        // Prepare the query
        $sql = "
            SELECT 
                AVG(TIME_TO_SEC(wakeup_time)) as avg_wakeup_time, 
                MIN(TIME_TO_SEC(wakeup_time)) as min_wakeup_time, 
                MAX(TIME_TO_SEC(wakeup_time)) as max_wakeup_time,

                AVG(day_naps) as avg_day_naps, 
                MIN(day_naps) as min_day_naps, 
                MAX(day_naps) as max_day_naps,
                
                AVG(total_hours_slept_day) as avg_total_hours_slept_day, 
                MIN(total_hours_slept_day) as min_total_hours_slept_day, 
                MAX(total_hours_slept_day) as max_total_hours_slept_day,
                
                AVG(TIME_TO_SEC(bedtime)) as avg_bedtime, 
                MIN(TIME_TO_SEC(bedtime)) as min_bedtime, 
                MAX(TIME_TO_SEC(bedtime)) as max_bedtime,
                
                AVG(TIME_TO_SEC(sleep_time)) as avg_sleep_time, 
                MIN(TIME_TO_SEC(sleep_time)) as min_sleep_time, 
                MAX(TIME_TO_SEC(sleep_time)) as max_sleep_time,
                
                AVG(TIME_TO_SEC(last_nap_wakeup)) as avg_last_nap_wakeup, 
                MIN(TIME_TO_SEC(last_nap_wakeup)) as min_last_nap_wakeup, 
                MAX(TIME_TO_SEC(last_nap_wakeup)) as max_last_nap_wakeup
            
            FROM (
                SELECT * 
                FROM analysis
                WHERE chat_id = ? AND analysis_date >= CURRENT_DATE - INTERVAL 1 MONTH 
                ORDER BY score DESC 
                LIMIT 5
            ) AS Top5;
    
        ";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $chat_id);
        $stmt->execute();
        $metrics = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $message = "<b><u>Las 5 mejores noches de los últimos 30 días</u></b>\n";
        $message .= "Hora de despertarse: " . gmdate("H:i", $metrics['avg_wakeup_time']) . " (Min: " . gmdate("H:i", $metrics['min_wakeup_time']) . ", Max: " . gmdate("H:i", $metrics['max_wakeup_time']) . ")\n";
        $message .= "Número de siestas: " . round($metrics['avg_day_naps'],1) . " (Min: {$metrics['min_day_naps']}, Max: {$metrics['max_day_naps']})\n";
        $message .= "Horas dormidas durante el día: " . round($metrics['avg_total_hours_slept_day'],1) . " horas (Min: " . round($metrics['min_total_hours_slept_day'],1) . ", Max: " . round($metrics['max_total_hours_slept_day'],1) . ")\n";
        $message .= "Hora de despertar de la última siesta: " . gmdate("H:i", $metrics['avg_last_nap_wakeup']) . " (Min: " . gmdate("H:i", $metrics['min_last_nap_wakeup']) . ", Max: " . gmdate("H:i", $metrics['max_last_nap_wakeup']) . ")";
        $message .= "Hora de acostarse: " . gmdate("H:i", $metrics['avg_bedtime']) . " (Min: " . gmdate("H:i", $metrics['min_bedtime']) . ", Max: " . gmdate("H:i", $metrics['max_bedtime']) . ")\n";
        $message .= "Hora de dormirse: " . gmdate("H:i", $metrics['avg_sleep_time']) . " (Min: " . gmdate("H:i", $metrics['min_sleep_time']) . ", Max: " . gmdate("H:i", $metrics['max_sleep_time']) . ")\n";

        $data = [
            'chat_id' => $chat_id,
            'text'    => $message,
            'parse_mode' => 'HTML'
        ];

        // Send the metrics message to the group
        $result = Request::sendMessage($data);

        if ($result->isOk()) {
            TelegramLog::notice("Metrics summary for chat ID $chat_id sent successfully");
        } else {
            TelegramLog::notice("Metrics summary for chat ID $chat_id was not sent");
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
