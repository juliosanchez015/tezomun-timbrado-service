<?php

return [
    'url' => env('TIMBRADO_URL', 'https://ws.comercio-digital.mx'),
    'url_cancel' => env('TIMBRADO_CANCEL_URL', 'https://cancela.comercio-digital.mx'),
    'url_sandbox' => env('TIMBRADO_URL_SANDBOX', 'https://pruebas.comercio-digital.mx'),
    'user' => env('TIMBRADO_USER'),
    'password' => env('TIMBRADO_PASSWORD'),
    'csd_key' => env('TIMBRADO_CSD_KEY'),
    'csd_pass' => env('TIMBRADO_CSD_PASS'),
    'csd_cer' => env('TIMBRADO_CSD_CER'),
    'csd_cer_pem' => env('TIMBRADO_CSD_CER_PEM'),
    'csd_key_pem' => env('TIMBRADO_CSD_KEY_PEM'),
];