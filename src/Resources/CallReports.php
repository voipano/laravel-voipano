<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

class CallReports extends Resource
{
    public function all(array $filters = []): array
    {
        return $this->client->request('GET', '/call-reports', $filters);
    }
}
