<?php

return [
    // Envio de e-mails pelo Brevo (brevo.com). Sem a chave, os e-mails não são
    // enviados; fora de produção, o link aparece em storage/logs/laravel.log.
    'brevo' => [
        'chave' => env('BREVO_API_KEY'),
        'remetente' => env('MAIL_FROM_ADDRESS'),
        'nome' => env('MAIL_FROM_NAME', 'Kamel Controle Financeiro'),
    ],

    // Captcha da Cloudflare (Turnstile). Sem a chave secreta, o captcha não é
    // exigido, como no ambiente local e nos testes automáticos.
    'turnstile' => [
        'chave_secreta' => env('TURNSTILE_SECRET_KEY'),
    ],
];
