<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

class Calls extends Resource
{
    /** Initiates a REAL voice OTP call; never automatically retried. */
    public function otp(string $phoneNumber, array $options = []): array
    {
        return $this->client->request('POST', '/call-otp', ['phone_number' => $phoneNumber] + $options);
    }

    /** Initiates a REAL outbound call; never automatically retried. */
    public function clickToCall(string $source, string $destination, array $options = []): array
    {
        return $this->client->request('POST', '/click-to-call', [
            'call_source' => $source,
            'call_destination' => $destination,
        ] + $options);
    }

    /** Initiates a REAL secure call; never automatically retried. */
    public function secureCall(string $source, string $destination, array $options = []): array
    {
        return $this->client->request('POST', '/secure-call', [
            'call_source' => $source,
            'call_destination' => $destination,
        ] + $options);
    }
}
