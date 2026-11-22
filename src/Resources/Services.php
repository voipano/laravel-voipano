<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

class Services extends Resource
{
    public function all(): array
    {
        return $this->client->request('GET', '/services');
    }
}
