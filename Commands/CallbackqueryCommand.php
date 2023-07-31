<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use mysqli;

class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'Handle callback queries';
    protected $version = '1.0.0';

    public function execute(): Longman\TelegramBot\Entities\ServerResponse
    {
        $callback_query = $this->getCallbackQuery();
        $callback_query_id = $callback_query->getId();
        $callback_data = $callback_query->getData();
    
        $message = $callback_query->getMessage();
        $chat_id = $message->getChat()->getId();
    
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);  // Abrimos la conexión aquí
    
        // Manejo de los comandos configurados en el teclado inline
        switch ($callback_data) {
            case 'command_new_message':
                $this->getTelegram()->executeCommand('newmessage');
                break;
            case 'command_edit_message':
                $this->getTelegram()->executeCommand('editmessage');
                break;
            default:
                if (strpos($callback_data, 'edit_') === 0) {
                    $message_id = substr($callback_data, 5);
            
                    // Guardar el estado en la base de datos (en este ejemplo, lo guardamos en una nueva tabla llamada 'user_state')
                    $stmt = $mysqli->prepare("INSERT INTO user_state (chat_id, state, message_id) VALUES (?, 'awaiting_new_message', ?)");
                    $stmt->bind_param('ii', $chat_id, $message_id);
                    $stmt->execute();
                    $stmt->close();
                
                    // Enviar un mensaje al usuario pidiendo el nuevo mensaje
                    $data = [
                        'chat_id' => $chat_id,
                        'text'    => 'Por favor, escribe el nuevo mensaje',
                    ];
                    $mysqli->close(); // Cerramos la conexión aquí
                    return Request::sendMessage($data);
                } else if (strpos($callback_data, 'delete_') === 0) {
                    $message_id = substr($callback_data, 7);
                    $stmt = $mysqli->prepare("DELETE FROM messages WHERE id = ? AND chat_id = ?");
                    $stmt->bind_param('si', $message_id, $chat_id);
                    $stmt->execute();
                    $stmt->close();
                
                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text'              => 'Mensaje eliminado con éxito',
                        'show_alert'        => true,  // Mostrar un alerta en lugar de una notificación.
                    ];
                    $mysqli->close(); // Cerramos la conexión aquí
                    return Request::answerCallbackQuery($data);
                }
                break;
        }
    }    
}
