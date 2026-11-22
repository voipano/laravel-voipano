<?php

declare(strict_types=1);

namespace Voipano\Laravel\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Voipano\Laravel\Http\Controllers\WebhookController;
use Voipano\Laravel\Tests\TestCase;
use Voipano\Laravel\Webhooks\Verifier;
use Voipano\Laravel\Webhooks\WebhookReceived;

class AcceptingTestVerifier implements Verifier
{
    public function verify(Request $request, string $secret): bool
    {
        return $secret === 'test-secret' && $request->header('X-Test-Signature') === 'accepted';
    }
}

class WebhookReceiverTest extends TestCase
{
    public function test_receiver_defaults_to_deny_all(): void
    {
        config()->set('voipano.webhooks.secret', 'test-secret');
        Event::fake();
        $request = Request::create('/webhooks/voipano', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"event":"ringing"}');
        $response = app(WebhookController::class)($request);
        self::assertSame(401, $response->getStatusCode());
        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_receiver_dispatches_only_after_verification(): void
    {
        config()->set('voipano.webhooks.secret', 'test-secret');
        config()->set('voipano.webhooks.verifier', AcceptingTestVerifier::class);
        Event::fake();
        $request = Request::create('/webhooks/voipano', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_TEST_SIGNATURE' => 'accepted',
        ], '{"event":"ringing"}');
        $response = app(WebhookController::class)($request);
        self::assertSame(200, $response->getStatusCode());
        Event::assertDispatched(WebhookReceived::class, fn (WebhookReceived $event) => $event->payload['event'] === 'ringing');
    }
    public function test_receiver_rejects_malformed_json_after_signature_verification(): void
    {
        config()->set('voipano.webhooks.secret', 'test-secret');
        config()->set('voipano.webhooks.verifier', AcceptingTestVerifier::class);
        Event::fake();
        $request = Request::create('/webhooks/voipano', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_TEST_SIGNATURE' => 'accepted',
        ], '{invalid');
        $response = app(WebhookController::class)($request);
        self::assertSame(400, $response->getStatusCode());
        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_receiver_rejects_json_list_even_after_signature_verification(): void
    {
        config()->set('voipano.webhooks.secret', 'test-secret');
        config()->set('voipano.webhooks.verifier', AcceptingTestVerifier::class);
        Event::fake();
        $request = Request::create('/webhooks/voipano', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_TEST_SIGNATURE' => 'accepted',
        ], '[1,2]');
        $response = app(WebhookController::class)($request);
        self::assertSame(400, $response->getStatusCode());
        Event::assertNotDispatched(WebhookReceived::class);
    }

}
