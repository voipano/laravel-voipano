<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

class MusicOnHolds extends Resource
{
    public function all(array $filters = []): array
    {
        return $this->client->request('GET', '/music-on-holds', $filters);
    }
}
