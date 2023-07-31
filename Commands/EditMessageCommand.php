<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

use mysqli;

class EditMessageCommand extends SystemCommand
{
    protected $name = 'editmessage';
    protected $description = 'Edit an existing message';
    protected $usage = '/editmessage';
    protected $version = '1.0.0';

    public function execute(): Longman\TelegramBot\Entities\ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        }

        $stmt = $mysqli->prepare("SELECT id, message_text FROM messages WHERE chat_id = ?");
        $stmt->bind_param('i', $chat_id);

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        $mysqli->close();

        $inline_keyboard = new InlineKeyboard([]);

        foreach ($messages as $message_db) {
            $inline_keyboard->addRow([
                ['text' => $message_db['message_text'], 'callback_data' => 'edit_' . $message_db['id']],
                ['text' => 'Eliminar', 'callback_data' => 'delete_' . $message_db['id']],
            ]);
        }

        $data = [
            'chat_id'      => $chat_id,
            'text'         => 'Selecciona un mensaje para editar o eliminar:',
            'reply_markup' => $inline_keyboard
        ];

        return Request::sendMessage($data);
    }
}
