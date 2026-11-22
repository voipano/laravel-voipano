<?php

declare(strict_types=1);

namespace Voipano\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Voipano\Laravel\Client;
use Voipano\Laravel\Tests\TestCase;

class EndpointsTest extends TestCase
{
    public static function endpoints(): array
    {
        return [
            'services' => ['GET', '/services', fn (Client $c) => $c->services()->all()],
            'contacts index' => ['GET', '/contacts', fn (Client $c) => $c->contacts()->all(['page' => 1])],
            'contacts show' => ['GET', '/contacts/abc', fn (Client $c) => $c->contacts()->find('abc')],
            'contacts create' => ['POST', '/contacts', fn (Client $c) => $c->contacts()->create(['primary_number' => '0912'])],
            'contacts patch' => ['PATCH', '/contacts/abc', fn (Client $c) => $c->contacts()->update('abc', ['first_name' => 'A'])],
            'contacts put' => ['PUT', '/contacts/abc', fn (Client $c) => $c->contacts()->updatePut('abc', ['first_name' => 'A'])],
            'contacts delete' => ['DELETE', '/contacts/abc', fn (Client $c) => $c->contacts()->delete('abc')],
            'contacts bulk' => ['POST', '/contacts/bulk-import', fn (Client $c) => $c->contacts()->bulkImport([['primary_number' => '0912']])],
            'groups index' => ['GET', '/contact-groups', fn (Client $c) => $c->contactGroups()->all()],
            'groups create' => ['POST', '/contact-groups', fn (Client $c) => $c->contactGroups()->create(['name' => 'VIP'])],
            'groups delete' => ['DELETE', '/contact-groups/g', fn (Client $c) => $c->contactGroups()->delete('g')],
            'music on hold' => ['GET', '/music-on-holds', fn (Client $c) => $c->musicOnHolds()->all()],
            'call reports' => ['GET', '/call-reports', fn (Client $c) => $c->callReports()->all()],
            'voicemail inboxes' => ['GET', '/voicemail-inboxes', fn (Client $c) => $c->voicemailInboxes()->all()],
            'call recordings' => ['GET', '/call-recordings/cdr', fn (Client $c) => $c->recordings()->call('cdr', 'cdr')],
            'voicemail recording' => ['GET', '/voicemail-recordings/vm', fn (Client $c) => $c->recordings()->voicemail('vm', 'default', '1001')],
            'webhooks get' => ['GET', '/call-events-webhook', fn (Client $c) => $c->webhooks()->get()],
            'webhooks put' => ['PUT', '/call-events-webhook', fn (Client $c) => $c->webhooks()->configure(['is_active' => false])],
            'webhooks route' => ['PUT', '/call-events-webhook', fn (Client $c) => $c->webhooks()->route('ringing', ['endpoint_url' => 'https://example.com'])],
            'webhooks legacy post' => ['POST', '/call-events-webhook', fn (Client $c) => $c->webhooks()->configureLegacy(['is_active' => false])],
            'voice otp' => ['POST', '/call-otp', fn (Client $c) => $c->calls()->otp('0912')],
            'click to call' => ['POST', '/click-to-call', fn (Client $c) => $c->calls()->clickToCall('0912', '0935')],
            'secure call' => ['POST', '/secure-call', fn (Client $c) => $c->calls()->secureCall('0912', '0935')],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_endpoint_method_and_url(string $method, string $path, \Closure $execute): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $client = app(Client::class);
        $execute($client);
        Http::assertSent(function (Request $request) use ($method, $path): bool {
            return $request->method() === $method
                && str_starts_with($request->url(), 'https://voipano.com/api/v1/webservice' . $path)
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('X-Request-Id')
                && $request->hasHeader('Accept');
        });
        Http::assertSentCount(1);
    }
}
