<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Exception\TelegramException;

class MessageHandlerCommand extends SystemCommand
{
    protected $name = 'messagehandler';
    protected $description = 'Handle group messages';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        // $text    = $message->getText(true);
        $chat_id = $message->getChat()->getId();
        $data = ['chat_id' => $chat_id];
        $data['text'] = 'Mensaje';

        return Request::sendMessage($data);

        // Conectar usando mysqli
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Si el mensaje es un comando
        if (strpos($text, '/') === 0) {
            $stmt = $mysqli->prepare("SELECT id FROM messages WHERE command = ?");
            $stmt->bind_param('s', $text);
            $stmt->execute();
            $stmt->bind_result($message_id);
            $stmt->fetch();

            if ($message_id) {
                $insertStmt = $mysqli->prepare("INSERT INTO events (chat_id, message_id) VALUES (?, ?)");
                $insertStmt->bind_param('ss', $chat_id, $message_id);
                $insertStmt->execute();
            }
            $stmt->close();

        } elseif (preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $text)) { // Si el mensaje es una hora
            $currentDate = new \DateTime();
            $givenTime = new \DateTime($text);
            $currentDate->setTime($givenTime->format('H'), $givenTime->format('i'));

            $updateStmt = $mysqli->prepare("UPDATE events SET datetime = ? WHERE chat_id = ? ORDER BY id DESC LIMIT 1");
            $formattedDate = $currentDate->format('Y-m-d H:i:s');
            $updateStmt->bind_param('ss', $formattedDate, $chat_id);
            $updateStmt->execute();
            $updateStmt->close();

        } else { // En cualquier otro caso, tratamos el mensaje como comentario
            $updateStmt = $mysqli->prepare("UPDATE events SET comment = ? WHERE chat_id = ? ORDER BY id DESC LIMIT 1");
            $updateStmt->bind_param('ss', $text, $chat_id);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $mysqli->close();

        return Request::emptyResponse();

    }
}
