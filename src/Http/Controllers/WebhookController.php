<?php

declare(strict_types=1);

namespace Voipano\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use Voipano\Laravel\Webhooks\Verifier;
use Voipano\Laravel\Webhooks\WebhookReceived;

class WebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('voipano.webhooks.secret', '');
        $verifierClass = (string) config('voipano.webhooks.verifier');
        $verifier = app($verifierClass);
        if ($secret === '' || !$verifier instanceof Verifier || !$verifier->verify($request, $secret)) {
            return response()->json(['ok' => false], 401);
        }
        try {
            $body = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
            if (!$body instanceof \stdClass) {
                return response()->json(['ok' => false], 400);
            }
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return response()->json(['ok' => false], 400);
        }
        event(new WebhookReceived($payload));
        return response()->json(['ok' => true]);
    }
}
