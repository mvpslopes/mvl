<?php
/**
 * Configuração do Banco de Dados - Sistema Real Driver
 * 
 * Este arquivo contém as configurações de conexão com o banco de dados
 * específico para o sistema Real Driver.
 */

return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'u179630068_realdriver', // Banco de dados dedicado para Real Driver
        'username' => 'u179630068_realdriveruser',
        'password' => 'KZbHRI3$',
        'charset' => 'utf8mb4'
    ],
    'system' => [
        'name' => 'Real Driver',
        'version' => '1.0.0',
        'table_prefix' => '' // Sem prefixo, pois é um banco dedicado
    ]
];

