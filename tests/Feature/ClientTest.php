<?php

declare(strict_types=1);

namespace Voipano\Laravel\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Voipano\Laravel\Client;
use Voipano\Laravel\Exceptions\AuthenticationException;
use Voipano\Laravel\Exceptions\RateLimitException;
use Voipano\Laravel\Exceptions\UnexpectedResponseException;
use Voipano\Laravel\Exceptions\ValidationException;
use Voipano\Laravel\Facades\Voipano;
use Voipano\Laravel\Tests\TestCase;

class ClientTest extends TestCase
{
    public function test_facade_and_service_selection_are_immutable(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        Voipano::forService('service-1')->withToken('user-token')->services()->all();
        Voipano::services()->all();
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Webservice-Service-Id', 'service-1')
            && $r->hasHeader('Authorization', 'Bearer user-token'));
        Http::assertSent(fn (Request $r) => !$r->hasHeader('X-Webservice-Service-Id')
            && $r->hasHeader('Authorization', 'Bearer test-token'));
        Http::assertSentCount(2);
    }

    public function test_request_id_can_be_supplied(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        app(Client::class)->withRequestId('my-trace')->services()->all();
        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Request-Id', 'my-trace'));
    }

    public function test_query_parameters_preserve_zero_false_and_encode_strings(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        app(Client::class)->contacts()->all(['search' => 'a b', 'page' => 0, 'empty' => '', 'nil' => null]);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'search=a%20b')
            || str_contains($r->url(), 'search=a+b'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'page=0')
            && !str_contains($r->url(), 'empty=') && !str_contains($r->url(), 'nil='));
    }

    public function test_error_types_and_server_request_id(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid token'], 401, ['X-Request-Id' => 'srv-id'])]);
        try {
            app(Client::class)->services()->all();
            self::fail('Expected authentication failure');
        } catch (AuthenticationException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('srv-id', $e->requestId);
        }
    }

    public function test_validation_errors_are_preserved(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid input', 'errors' => ['phone' => ['required']]], 422)]);
        try {
            app(Client::class)->calls()->otp('');
            self::fail('Expected validation failure');
        } catch (ValidationException $e) {
            self::assertSame(['phone' => ['required']], $e->errors);
        }
    }

    public function test_write_requests_are_not_retried_on_rate_limit(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Too many'], 429)]);
        try {
            app(Client::class)->calls()->clickToCall('0912', '0935');
            self::fail('Expected 429');
        } catch (RateLimitException $e) {
            self::assertSame(429, $e->statusCode);
        }
        Http::assertSentCount(1);
    }

    public function test_invalid_json_is_not_mistaken_for_success(): void
    {
        Http::fake(['*' => Http::response('<html>bad</html>', 200, ['Content-Type' => 'text/html'])]);
        $this->expectException(UnexpectedResponseException::class);
        app(Client::class)->services()->all();
    }

    public function test_missing_token_rejected_before_http_request(): void
    {
        Http::fake();
        $this->expectException(InvalidArgumentException::class);
        app(Client::class)->withToken('')->services()->all();
    }

    public function test_contact_ids_cannot_override_paths(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        app(Client::class)->contacts()->find('a/b');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/contacts/a%2Fb'));
    }
    public function test_empty_success_body_returns_empty_array(): void
    {
        Http::fake(['*' => Http::response('', 204)]);
        self::assertSame([], app(Client::class)->contacts()->delete('contact'));
    }

    public function test_binary_download_rejects_json_in_success_response(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Not an audio file'], 200, [
            'Content-Type' => 'application/json',
        ])]);
        $this->expectException(UnexpectedResponseException::class);
        app(Client::class)->recordings()->call('cdr', 'record');
    }

    public function test_get_does_not_omit_false_or_zero_query_values(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        app(Client::class)->callReports()->all(['has_recording' => 0, 'inactive' => false]);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'has_recording=0')
            && str_contains($r->url(), 'inactive=0'));
    }

    public function test_post_calls_use_json_content_type_and_are_not_retried(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        app(Client::class)->calls()->secureCall('09120000000', '09350000000');
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->hasHeader('Content-Type', 'application/json')
            && $r->hasHeader('Authorization', 'Bearer test-token'));
        Http::assertSentCount(1);
    }

}
