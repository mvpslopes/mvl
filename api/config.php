<?php
/**
 * Configurações do Google Analytics
 * 
 * Estas informações foram obtidas do painel do Google Analytics:
 * - Nome do Fluxo: mvlopes
 * - URL do Fluxo: https://mvlopes.com.br
 * - Property ID: 517334916 (ID da Propriedade - encontrado em Administração > Detalhes da propriedade)
 * - Código do Fluxo: 13183308243 (Stream ID - diferente do Property ID)
 * - ID da Métrica: G-6ZCVW4LQG9 (Measurement ID)
 */

return [
    'ga4' => [
        'property_id' => '517334916',
        'measurement_id' => 'G-6ZCVW4LQG9',
        'credentials_path' => __DIR__ . '/credentials.json', // Arquivo de credenciais OAuth 2.0
    ],
    'api' => [
        'enabled' => true, // API configurada e pronta
        'use_mock_data' => false, // Usando dados reais do Google Analytics
    ],
];

