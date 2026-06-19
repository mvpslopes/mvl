<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'seu_banco',
        'username' => 'seu_usuario',
        'password' => 'sua_senha',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'token_ttl_hours' => 168,
        'session_name' => 'FINSESSID',
    ],
    'auth' => [
        'username' => 'admin',
        // Gere com: php -r "echo password_hash('sua_senha', PASSWORD_DEFAULT);"
        'password_hash' => '$2y$10$SUBSTITUA_PELO_HASH_DA_SUA_SENHA',
    ],
];
