<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

use mysqli;

class NewMessageCommand extends SystemCommand
{
    protected $name = 'newmessage';
    protected $description = 'Start a new message';
    protected $usage = '/newmessage';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $callback_query = $this->getCallbackQuery();
    
        // Comprobar si la consulta de devolución de llamada está disponible
        if ($callback_query) {
            $chat_id = $callback_query->getMessage()->getChat()->getId();
        } else {
            $chat_id = $message->getChat()->getId();

            // Primero, comprobamos si el mensaje es una respuesta a un mensaje anterior
            $reply_to_message = $message->getReplyToMessage();
        }

        if ($reply_to_message && $reply_to_message->getText() === 'Por favor, introduzca el mensaje que quiere guardar:') {
            // El mensaje es una respuesta. Procesamos el texto del mensaje y lo guardamos en la base de datos
            $text = $message->getText();

            // Generamos un identificador único
            $id = substr(bin2hex(random_bytes(8)), 0, 8); 

            // Guardamos el mensaje en la base de datos
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($mysqli->connect_errno) {
                echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
            }

            $stmt = $mysqli->prepare("INSERT INTO messages (id, chat_id, text) VALUES (?, ?, ?)");
            $stmt->bind_param('sis', $id, $chat_id, $text);

            if (!$stmt->execute()) {
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }

            $stmt->close();
            $mysqli->close();

            $data = [
                'chat_id' => $chat_id,
                'text'    => 'Su mensaje ha sido guardado con el ID: ' . $id,
            ];
        } else {
            // El mensaje no es una respuesta, por lo que le pedimos al usuario que introduzca el mensaje que quiere guardar
            $data = [
                'chat_id'      => $chat_id,
                'text'         => 'Por favor, introduzca el mensaje que quiere guardar:',
                'reply_markup' => json_encode(['force_reply' => true, 'selective' => true]),
            ];
        }

        return Request::sendMessage($data);
    }
}
