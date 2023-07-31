<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'Handle callback queries';
    protected $version = '1.0.0';

    public function execute()
    {
        $callback_query = $this->getCallbackQuery();
        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();

        $message = $callback_query->getMessage();
        $chat_id = $message->getChat()->getId();
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (strpos($callback_data, 'edit_') === 0) {
            $message_id = substr($callback_data, 5);
            // Aquí puedes solicitar el nuevo mensaje y actualizar la base de datos.
        } else if (strpos($callback_data, 'delete_') === 0) {
            $message_id = substr($callback_data, 7);
            $stmt = $mysqli->prepare("DELETE FROM messages WHERE id = ? AND chat_id = ?");
            $stmt->bind_param('si', $message_id, $chat_id);
            $stmt->execute();
            $stmt->close();
        }

        $mysqli->close();

        $data = [
            'callback_query_id' => $callback_query_id,
            'text'              => 'Mensaje actualizado o eliminado con éxito',
            'show_alert'        => true,  // Mostrar un alerta en lugar de una notificación.
        ];

        return Request::answerCallbackQuery($data);
    }
}
