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

/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 *
 * In this message-related context, we can handle any kind of message.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        /**
         * Handle any kind of message here
         */

        $text = $message->getText(true);

        // Conectar usando mysqli
        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $text)) { // Si el mensaje es una hora
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

        return $this->replyToChat("Mensaje procesado: " . $text);

        return Request::emptyResponse();
    }
}