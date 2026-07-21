<?php

declare(strict_types=1);

return [
    'api_key' => env('ASAAS_API_KEY'),
    'environment' => env('ASAAS_ENVIRONMENT', 'sandbox'),
    'timeout' => (int) (env('ASAAS_TIMEOUT') ?: 30),
    'connect_timeout' => (int) (env('ASAAS_CONNECT_TIMEOUT') ?: 10),
    'throw_on_transport_failure' => filter_var(env('ASAAS_THROW_ON_TRANSPORT_FAILURE', false), FILTER_VALIDATE_BOOLEAN),
];
