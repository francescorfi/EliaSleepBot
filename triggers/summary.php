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
            $dateObject = DateTime::createFromFormat('Y-m-d', $analysis['analysis_date']);
            $message = "<b><u>Resumen del día " . $dateObject->format('d/m/Y') . "</u></b>\n";
            $message .= "<b>Puntuación total:</b> " . round($analysis['score'],2) . "\n";
            $message .= "\n";
            $message .= "<b>Sueño diurno:</b>\n";
            $message .= "Número de siestas: {$analysis['day_naps']}\n";
            $message .= "Horas dormidas durante el día: " . round($analysis['total_hours_slept_day'],1) . " horas\n";
            $message .= "Despertar de la última siesta: " . date("H:i", strtotime($analysis['last_nap_wakeup'])) . "\n";
            $message .= "Máximo tiempo despierta: " . round($analysis['max_awake_period'],1) . " horas\n";
            $message .= "\n";
            $message .= "<b>Sueño nocturno:</b>\n";
            $message .= "Hora de acostarse: " . date("H:i", strtotime($analysis['bedtime'])) . "\n";
            $message .= "Hora de dormirse: " . date("H:i", strtotime($analysis['sleep_time'])) . " (Conciliación: " . round($analysis['time_to_fall_asleep'],0) . " minutos)\n";
            $message .= "Número de despertares: {$analysis['night_wakeups']}\n";
            $message .= "Periodos largos de sueño: {$analysis['number_of_long_sleep_periods']}\n";
            $message .= "Duración del periodo más largo: " . round($analysis['max_night_sleep_period'], 1) . " horas\n";
            $message .= "Duración del segundo periodo más largo: " . round($analysis['second_max_night_sleep_period'], 1) . " horas\n";
            $message .= "Número total de horas dormidas: " . round($analysis['total_hours_slept_night'],1) . " horas\n";
            $message .= "Número de tomas: {$analysis['number_of_breastfeeding_events']}";

            $data = [
                'chat_id' => $chat_id,
                'text'    => $message,
                'parse_mode' => 'HTML'
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
