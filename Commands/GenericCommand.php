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
            file_get_contents(TOP5_URL . $chat_id);
        }

        if ($command == "resumen") {
            shell_exec('/home/shuvkktd/virtualenv/repositories/EliaSleepAnalytics/3.9/bin/python /home/shuvkktd/repositories/EliaSleepAnalytics/script.py');
        }


        if ($command == "horas") {
            $logFile = '/home/shuvkktd/bots.francescorfi.com/EliaSleepBot/log.txt';
            
            file_put_contents($logFile, "Comenzando el comando /horas\n", FILE_APPEND);
            
            try {
                // Obtén la fecha y hora actuales
                $now = new DateTime();
                
                // Establece la hora a las 8 de la mañana
                $now->setTime(8, 0);
                
                file_put_contents($logFile, "Hora actual: " . $now->format('Y-m-d H:i:s') . "\n", FILE_APPEND);
                
                // Prepara la consulta SQL
                $stmt = $mysqli->prepare("SELECT message_id, datetime FROM events WHERE (message_id = 2 OR message_id = 3) AND datetime >= ? AND chat_id = ? ORDER BY datetime");
                $stmt->bind_param('ss', $now->format('Y-m-d H:i:s'), $chat_id);
                
                file_put_contents($logFile, "Consulta SQL preparada\n", FILE_APPEND);
                
                // Ejecuta la consulta
                $stmt->execute();
                $stmt->bind_result($message_id, $datetime);
                
                file_put_contents($logFile, "Consulta SQL ejecutada\n", FILE_APPEND);
                
                // Inicializa variables
                $lastDatetime = null;
                $totalHours = 0;
                
                // Procesa los resultados
                while ($stmt->fetch()) {
                    $currentDatetime = new DateTime($datetime);
                    
                    // Si el último datetime no es nulo y el mensaje actual es un "despertar" (message_id = 3) y el último mensaje fue un "dormir" (message_id = 2),
                    // entonces calcula la diferencia de tiempo y suma al total de horas
                    if ($lastDatetime !== null && $message_id == 3 && $lastMessageId == 2) {
                        $interval = $lastDatetime->diff($currentDatetime);
                        $totalHours += $interval->h + $interval->days * 24;
                    }
                    
                    $lastDatetime = $currentDatetime;
                    $lastMessageId = $message_id;
                }
                
                file_put_contents($logFile, "Resultados procesados\n", FILE_APPEND);
                
                $stmt->close();
                
                // Envía la respuesta
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => 'Total de horas dormidas desde las 8 de la mañana: ' . $totalHours,
                ];
                return Request::sendMessage($data);
            } catch (Exception $e) {
                file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
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