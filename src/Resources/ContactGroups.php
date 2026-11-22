<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

class ContactGroups extends Resource
{
    public function all(array $filters = []): array
    {
        return $this->client->request('GET', '/contact-groups', $filters);
    }

    public function create(array $data): array
    {
        return $this->client->request('POST', '/contact-groups', $data);
    }

    public function delete(string|int $id): array
    {
        return $this->client->request('DELETE', '/contact-groups/' . self::segment($id));
    }
}
