<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Planilla Simple',
    'url' => function_exists('getBaseUrl') ? getBaseUrl() : ($_ENV['APP_URL'] ?? 'http://localhost'),
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City',
    'locale' => $_ENV['APP_LOCALE'] ?? 'es',
    
    'session' => [
        'name' => 'planilla_session',
        'lifetime' => 120,
        'secure' => false,
        'httponly' => true
    ],
    
    'security' => [
        'csrf_protection' => true,
        'password_min_length' => 8
    ]
];