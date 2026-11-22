<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

use InvalidArgumentException;

class Contacts extends Resource
{
    public function all(array $filters = []): array
    {
        return $this->client->request('GET', '/contacts', $filters);
    }

    public function find(string|int $id): array
    {
        return $this->client->request('GET', '/contacts/' . self::segment($id));
    }

    public function create(array $data): array
    {
        return $this->client->request('POST', '/contacts', $data);
    }

    /** Only supplied fields are changed. Empty arrays may clear existing values. */
    public function update(string|int $id, array $data): array
    {
        return $this->client->request('PATCH', '/contacts/' . self::segment($id), $data);
    }

    /** PUT is documented as a partial update alias, not full replacement. */
    public function updatePut(string|int $id, array $data): array
    {
        return $this->client->request('PUT', '/contacts/' . self::segment($id), $data);
    }

    public function delete(string|int $id): array
    {
        return $this->client->request('DELETE', '/contacts/' . self::segment($id));
    }

    public function bulkImport(array $contacts, array $options = []): array
    {
        if (count($contacts) < 1 || count($contacts) > 3000 || !array_is_list($contacts)) {
            throw new InvalidArgumentException('contacts must be a list containing 1 to 3000 entries.');
        }
        return $this->client->request('POST', '/contacts/bulk-import', ['contacts' => $contacts] + $options);
    }
}
