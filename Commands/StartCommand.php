<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class StartCommand extends SystemCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $chat_type = $message->getChat()->getType();
        $data = ['chat_id' => $chat_id];
        $keyboard = new InlineKeyboard([
            ['text' => 'Crear nuevo mensaje', 'callback_data' => 'command_new_message'],
            ['text' => 'Editar mensajes existentes', 'callback_data' => 'command_edit_message']
        ]);

        if ($chat_type === 'private') {
            $data['text'] = '¡Hola! Este bot está diseñado para trabajar en grupos. Por favor, añádeme a un grupo para empezar.';
        } else {
            $data['text'] = '¡Hola, grupo! ¿Qué te gustaría hacer?';
            $data['reply_markup'] = $keyboard;
            $data['reply_markup']->setResizeKeyboard(true);
        }

        return Request::sendMessage($data);
    }
}
