<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * Generic command
 *
 * Gets executed for generic commands, when no other appropriate one is found.
 */
class GenericCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'generic';

    /**
     * @var string
     */
    protected $description = 'Handles generic commands or is executed by default when a command is not found';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $command = $message->getCommand();
        $chat_type = $message->getChat()->getType(); // Añadido para determinar el tipo de chat

        // Conectar usando mysqli
        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
        // Verificar si es un chat privado
        if ($chat_type == 'private') {
            return Request::sendMessage(['chat_id' => $chat_id, 'text' => '¡Lo siento! Solo funciono en grupos, crea un grupo con el nombre de tu hijo/a y dalo de alta en eliasleep.com.']);
        }
    
        // Verificar si el chat_id del grupo está en la tabla chats
        $checkChatStmt = $mysqli->prepare("SELECT COUNT(*) FROM chats WHERE chat_id = ?");
        $checkChatStmt->bind_param('s', $chat_id);
        $checkChatStmt->execute();
        $checkChatStmt->bind_result($count);
        $checkChatStmt->fetch();
        $checkChatStmt->close();
    
        if ($count == 0) {
            return Request::sendMessage(['chat_id' => $chat_id, 'text' => 'Por favor, dirígete a eliasleep.com para registrar este chat y poder empezar a controlar las horas de sueño. Este es el ID que debes informar en la aplicación: ' . $chat_id]);
        }

        if ($command == "top5") {
            file_get_contents(TOP5_URL);
        }

        // Si el mensaje es un comando
        $stmt = $mysqli->prepare("SELECT id FROM messages WHERE command = ?");
        $commandWithSlash = "/" . $command;
        $stmt->bind_param('s', $commandWithSlash);
        $stmt->execute();
        $stmt->bind_result($message_id);
        $stmt->fetch();
        $stmt->free_result();  // Liberar el resultado.
        $stmt->close();

        if ($message_id) {
            $insertStmt = $mysqli->prepare("INSERT INTO events (chat_id, message_id) VALUES (?, ?)");
            $insertStmt->bind_param('ss', $chat_id, $message_id);
            $insertStmt->execute();
            $insertStmt->close();
        }

        return Request::emptyResponse();
    }
}