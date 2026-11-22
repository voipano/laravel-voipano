<?php

declare(strict_types=1);

namespace Voipano\Laravel\Webhooks;

/** Fired only after the user-supplied verifier accepts an incoming webhook. */
class WebhookReceived
{
    public function __construct(public readonly array $payload) {}
}
