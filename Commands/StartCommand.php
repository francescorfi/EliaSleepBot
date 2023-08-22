<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\TelegramLog;

class StartCommand extends SystemCommand
{
    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $chat_type = $message->getChat()->getType();
        $data = ['chat_id' => $chat_id];

        if ($chat_type === 'private') {
            $data['text'] = '¡Hola! Este bot está diseñado para trabajar en grupos. Por favor, añádeme a un grupo para empezar.';
        } else {
            $data['text'] = "¡Hola! El ID de este grupo es: $chat_id. Por favor, utilízalo para configurar el grupo en la aplicación.";
        }

        return Request::sendMessage($data);
    }
}
