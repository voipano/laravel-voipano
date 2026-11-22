<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

use InvalidArgumentException;
use Voipano\Laravel\Client;

abstract class Resource
{
    public function __construct(protected readonly Client $client)
    {
    }

    protected static function segment(string|int $id): string
    {
        if (trim((string) $id) === '') {
            throw new InvalidArgumentException('Resource identifier must not be empty.');
        }
        return rawurlencode((string) $id);
    }
}
