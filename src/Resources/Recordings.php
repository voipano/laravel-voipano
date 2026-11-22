<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

use Illuminate\Http\Client\Response;

class Recordings extends Resource
{
    /** Both identifiers are required by the documented call recording endpoint. */
    public function call(string|int $cdrId, string|int $callDetailsRecordId, array $query = []): Response
    {
        return $this->client->binary('/call-recordings/' . self::segment($cdrId),
            ['call_details_record_id' => (string) $callDetailsRecordId] + $query);
    }

    public function downloadCall(string|int $cdrId, string|int $callDetailsRecordId, string $destination, array $query = []): string
    {
        return $this->client->downloadTo('/call-recordings/' . self::segment($cdrId), $destination,
            ['call_details_record_id' => (string) $callDetailsRecordId] + $query);
    }

    public function voicemail(string|int $uniqueId, string $context, string $mailbox, array $query = []): Response
    {
        return $this->client->binary('/voicemail-recordings/' . self::segment($uniqueId),
            ['context' => $context, 'mailbox' => $mailbox] + $query);
    }

    public function downloadVoicemail(string|int $uniqueId, string $context, string $mailbox, string $destination, array $query = []): string
    {
        return $this->client->downloadTo('/voicemail-recordings/' . self::segment($uniqueId), $destination,
            ['context' => $context, 'mailbox' => $mailbox] + $query);
    }
}
