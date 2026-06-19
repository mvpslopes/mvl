<?php
/**
 * Copie para config.local.php e preencha com os dados do banco na Hostinger.
 */
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'u179630068_sesmt_bd',
        'username' => 'u179630068_sesmt_user',
        'password' => 'SUA_SENHA_AQUI',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'token_ttl_hours' => 24,
        'session_name' => 'SESMTSESSID',
    ],
];
