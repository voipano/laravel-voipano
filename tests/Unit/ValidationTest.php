<?php

declare(strict_types=1);

namespace Voipano\Laravel\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Voipano\Laravel\Client;

class ValidationTest extends TestCase
{
    public function test_invalid_base_url_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client('token', 'voipano.com');
    }

    public function test_contact_bulk_limit_is_validated_without_network(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Client('token'))->contacts()->bulkImport([]);
    }

    public function test_unknown_webhook_event_is_rejected_without_network(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Client('token'))->webhooks()->route('unknown', []);
    }

    public function test_request_id_rejects_newlines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Client('token'))->withRequestId("x\nInjected: true");
    }
}
