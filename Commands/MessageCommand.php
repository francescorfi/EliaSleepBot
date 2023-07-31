<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

use mysqli;

class MessageCommand extends SystemCommand
{
    protected $name = 'message';
    protected $description = 'Handle user messages';
    protected $usage = '/message';
    protected $version = '1.0.0';

    public function execute()
    {
        $message = $this->getMessage();
        $text    = trim($message->getText(true));
        $chat_id = $message->getChat()->getId();

        // Crear conexión a la base de datos
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Comprobar si el usuario está en un estado en el que estamos esperando un mensaje
        $stmt = $mysqli->prepare("SELECT state, message_id FROM user_state WHERE chat_id = ?");
        $stmt->bind_param('i', $chat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $state = $row['state'];
        $message_id = $row['message_id'];

        if ($state == 'awaiting_new_message') {
            // Si es así, actualizar el mensaje con el texto recibido
            $stmt = $mysqli->prepare("UPDATE messages SET message_text = ? WHERE id = ? AND chat_id = ?");
            $stmt->bind_param('sii', $text, $message_id, $chat_id);
            $stmt->execute();

            // Y eliminar el estado del usuario, ya que ya hemos manejado el nuevo mensaje
            $stmt = $mysqli->prepare("DELETE FROM user_state WHERE chat_id = ?");
            $stmt->bind_param('i', $chat_id);
            $stmt->execute();

            // Cerrar la sentencia
            $stmt->close();

            // Enviar un mensaje al usuario para confirmar que el mensaje ha sido actualizado
            $data = [
                'chat_id' => $chat_id,
                'text'    => 'El mensaje ha sido actualizado',
            ];
            return Request::sendMessage($data);
        }

        // Cerrar la conexión a la base de datos
        $mysqli->close();

        // Si no estamos esperando un nuevo mensaje, entonces sólo reenvía el mensaje como un echo
        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];
        return Request::sendMessage($data);
    }
}
