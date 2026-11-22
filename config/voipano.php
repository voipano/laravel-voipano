<?php

declare(strict_types=1);

use Voipano\Laravel\Webhooks\RejectAllVerifier;

return [
    'base_url' => env('VOIPANO_BASE_URL', 'https://voipano.com/api/v1/webservice'),
    'token' => env('VOIPANO_API_TOKEN', ''),
    'service_id' => env('VOIPANO_SERVICE_ID', ''),
    'timeout' => (int) env('VOIPANO_TIMEOUT', 30),
    'connect_timeout' => (int) env('VOIPANO_CONNECT_TIMEOUT', 10),
    'webhooks' => [
        // No incoming route is exposed unless explicitly enabled.
        'enabled' => (bool) env('VOIPANO_WEBHOOK_RECEIVER_ENABLED', false),
        'path' => env('VOIPANO_WEBHOOK_PATH', 'webhooks/voipano'),
        'secret' => env('VOIPANO_WEBHOOK_SECRET', ''),
        // Signature specification is not provided in Postman.
        // Implement Verifier and explicitly configure it before enabling.
        'verifier' => RejectAllVerifier::class,
        'middleware' => ['api'],
    ],
];
