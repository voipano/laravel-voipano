<?php

declare(strict_types=1);

namespace Voipano\Laravel\Exceptions;

class ApiException extends VoipanoException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?string $requestId = null,
        public readonly array $errors = [],
    ) {
        parent::__construct($message, $statusCode);
    }
}
