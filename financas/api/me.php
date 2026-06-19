<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

fin_cors();
fin_options_exit();
fin_require_auth();

fin_json([
    'success' => true,
    'name' => fin_config()['auth']['display_name'] ?? 'Finanças',
    'username' => fin_config()['auth']['username'] ?? 'admin',
]);
