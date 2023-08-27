<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');

define('TOP5_URL', 'https://bots.francescorfi.com/EliaSleepBot/triggers/top5.php?id=');

$settings = [
    'api_key'      => 'YOUR_BOT_API_KEY',
    'bot_username' => 'YOUR_BOT_USERNAME',
    'commands_paths' => [
        __DIR__ . '/Commands',
    ],
    'hook_url' => 'https://your-domain/path/to/hook.php',
];
