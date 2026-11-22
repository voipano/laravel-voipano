<?php

declare(strict_types=1);

namespace Voipano\Laravel\Resources;

class VoicemailInboxes extends Resource
{
    public function all(array $filters = []): array
    {
        return $this->client->request('GET', '/voicemail-inboxes', $filters);
    }
}
