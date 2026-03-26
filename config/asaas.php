<?php

declare(strict_types=1);

return [
    'api_key' => env('ASAAS_API_KEY'),
    'environment' => env('ASAAS_ENVIRONMENT', 'sandbox'),
    'timeout' => env('ASAAS_TIMEOUT', 30),
];
