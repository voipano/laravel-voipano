<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

use InvalidArgumentException;

class Webhooks extends Resource
{
    public const EVENTS = ['ringing', 'hangup', 'missed_call', 'call_details_record'];

    public function get(): array

    {

        return $this->client->request('GET', '/call-events-webhook');

    }

    /** PUT general webhook config. Set endpoint_url='' and is_active=false to disable. */
    public function configure(array $settings): array
    {
        return $this->client->request('PUT', '/call-events-webhook', $settings);
    }

    /** PUT an event-specific webhook route; empty endpoint_url removes this route. */
    public function route(string $event, array $settings): array
    {
        if (!in_array($event, self::EVENTS, true)) {
            throw new InvalidArgumentException('Unsupported Voipano call event.');
        }
        return $this->client->request('PUT', '/call-events-webhook', ['event' => $event] + $settings);
    }

    /** Legacy POST alias; prefer configure(). */
    public function configureLegacy(array $settings): array
    {
        return $this->client->request('POST', '/call-events-webhook', $settings);
    }
}
