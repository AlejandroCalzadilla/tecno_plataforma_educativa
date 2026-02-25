<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pago Fácil Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Pago Fácil
    |
    */

    'base_url' => env('PAGOFACIL_BASE_URL', 'https://serviciostigomoney.pagofacil.com.bo'),
    
    'token_service' => env('PAGOFACIL_TOKEN_SERVICE', '51247fae280c20410824977b0781453df59fad5b23bf2a0d14e884482f91e09078dbe5966e0b970ba696ec4caf9aa5661802935f86717c481f1670e63f35d5041c31d7cc6124be82afedc4fe926b806755efe678917468e31593a5f427c79cdf016b686fca0cb58eb145cf524f62088b57c6987b3bb3f30c2082b640d7c52907'),
    
    'token_secret' => env('PAGOFACIL_TOKEN_SECRET', '9E7BC239DDC04F83B49FFDA5'),
    
   // 'commerce_id' => env('PAGOFACIL_COMMERCE_ID', 'd029fa3a95e174a19934857f535eb9427d967218a36ea014b70ad704bc6c8d1c'),
    
    // URLs de callback y retorno
    'callback_url' => env('PAGOFACIL_CALLBACK_URL', env('APP_URL', 'http://localhost') . '/pagofacil/callback'),
    
    'return_url' => env('PAGOFACIL_RETURN_URL', env('APP_URL', 'http://localhost') . '/pagofacil/return'),
    
    // Configuraciones adicionales
    'timeout' => env('PAGOFACIL_TIMEOUT', 30),
    
    'currency' => 2, // 2 = BOB (Bolivianos)
    
    // Habilitar/deshabilitar logs
    'enable_logs' => env('PAGOFACIL_ENABLE_LOGS', true),
    
    // Entorno (sandbox o production)
    'environment' => env('PAGOFACIL_ENVIRONMENT', 'sandbox'),
];