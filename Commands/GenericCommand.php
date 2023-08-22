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

        // Conectar usando mysqli
        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Si el mensaje es un comando
        $stmt = $mysqli->prepare("SELECT id FROM messages WHERE command = ?");
        $stmt->bind_param('s', '/' . $command);
        $stmt->execute();
        $stmt->bind_result($message_id);
        $stmt->fetch();

        if ($message_id) {
            $insertStmt = $mysqli->prepare("INSERT INTO events (chat_id, message_id) VALUES (?, ?)");
            $insertStmt->bind_param('ss', $chat_id, $message_id);
            $insertStmt->execute();
        }

        $stmt->close();

        return $this->replyToChat("Command /{$command} not found.. :(");
    }
}