<?php

declare(strict_types=1);

namespace Voipano\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Voipano\Laravel\Client;

/**
 * @method static \Voipano\Laravel\Client withToken(string $token)
 * @method static \Voipano\Laravel\Client forService(string $serviceId)
 * @method static \Voipano\Laravel\Client withRequestId(string $requestId)
 * @method static \Voipano\Laravel\Resources\Services services()
 * @method static \Voipano\Laravel\Resources\Contacts contacts()
 * @method static \Voipano\Laravel\Resources\ContactGroups contactGroups()
 * @method static \Voipano\Laravel\Resources\MusicOnHolds musicOnHolds()
 * @method static \Voipano\Laravel\Resources\CallReports callReports()
 * @method static \Voipano\Laravel\Resources\VoicemailInboxes voicemailInboxes()
 * @method static \Voipano\Laravel\Resources\Recordings recordings()
 * @method static \Voipano\Laravel\Resources\Webhooks webhooks()
 * @method static \Voipano\Laravel\Resources\Calls calls()
 */
class Voipano extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
