<?php

// Quais sites podem chamar a API pelo navegador. Em produção, a variável
// CORS_ALLOWED_ORIGINS traz o endereço do front-end (vários, separados por vírgula).
$origens = env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173');

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(array_map(
        fn (string $origem) => rtrim(trim($origem), '/'),
        explode(',', $origens),
    ))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
