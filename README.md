# Voipano Laravel SDK

[![Tests](https://github.com/voipano/laravel-voipano/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/voipano/laravel-voipano/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/voipano/laravel)](https://packagist.org/packages/voipano/laravel)
[![License](https://img.shields.io/github/license/voipano/laravel-voipano)](LICENSE)
[![Documentation](https://img.shields.io/badge/docs-EN%20%7C%20FA-blue)](README.md)

[راهنمای فارسی](README.fa.md) | [Postman collection](docs/voipano-webservice.postman_collection.json) | [Security](SECURITY.md)

A Laravel package for the **Voipano Public Webservice API v1**, providing an easy-to-use facade, typed exceptions, and resource clients for all **23 requests** in the official Postman collection (documentation version `2026.11.21`).

## Requirements

- **PHP 8.2+**; PHP 8.2 receives security fixes only until 31 December 2026. Prefer PHP 8.3+ for new projects.
- **Laravel 12** on PHP 8.2+ or **Laravel 13** on PHP 8.3+.
- Composer 2.

## Installation

Install the published package from Packagist:

```bash
composer require voipano/laravel:^1.0
```

Laravel automatically discovers the service provider. Set the API token in your **application's** `.env`:

```dotenv
VOIPANO_API_TOKEN=your-private-token
# Optional if the token is not bound to a single service:
VOIPANO_SERVICE_ID=
```

Nothing else is needed for outgoing API calls. To customize timeouts, the endpoint, or inbound webhooks, publish the configuration:

```bash
php artisan vendor:publish --tag=voipano-config
```

Optional environment variables: `VOIPANO_BASE_URL` (default `https://voipano.com/api/v1/webservice`), `VOIPANO_TIMEOUT` (default `30`) and `VOIPANO_CONNECT_TIMEOUT` (default `10`). Never commit the `.env` file.

## Basic usage

```php
use Voipano\Laravel\Facades\Voipano;

$services = Voipano::services()->all();
$contacts = Voipano::contacts()->all(['search' => 'Ali', 'page' => 1, 'per_page' => 20]);
$contact = Voipano::contacts()->find('contact-id');

Voipano::contacts()->create([
    'primary_number' => '09120000000',
    'first_name' => 'Ali',
]);
Voipano::contacts()->update('contact-id', ['first_name' => 'Ali']);

// These methods place REAL calls. Call them only from authorized application flows.
// Voipano::calls()->otp('09120000000', ['purpose' => 'login']);
// Voipano::calls()->clickToCall('09120000000', '09350000000', ['timeout' => 30]);
// Voipano::calls()->secureCall('09120000000', '09350000000', ['retry_count' => 0]);
```

For multiple services or tenants, use immutable scoped clients; the global configuration is unaffected:

```php
$client = Voipano::withToken($customerToken)->forService($serviceId);
$reports = $client->callReports()->all(['page' => 1]);
$traced = $client->withRequestId('my-request-id')->services()->all();
```

## Resources

| Client | Methods |
| --- | --- |
| `services()` | `all()` |
| `contacts()` | `all($filters)`, `find($id)`, `create($data)`, `update($id, $data)`, `updatePut($id, $data)`, `delete($id)`, `bulkImport($contacts, $options)` |
| `contactGroups()` | `all($filters)`, `create($data)`, `delete($id)` |
| `musicOnHolds()` | `all($filters)` |
| `callReports()` | `all($filters)` |
| `voicemailInboxes()` | `all($filters)` |
| `recordings()` | `call($cdrId, $recordId, $query)`, `downloadCall($cdrId, $recordId, $destination, $query)`, `voicemail($id, $context, $mailbox, $query)`, `downloadVoicemail($id, $context, $mailbox, $destination, $query)` |
| `webhooks()` | `get()`, `configure($settings)`, `route($event, $settings)`, `configureLegacy($settings)` |
| `calls()` | `otp($phone, $options)`, `clickToCall($source, $destination, $options)`, `secureCall($source, $destination, $options)` |

Each JSON endpoint returns the API's JSON decoded to a PHP array (no speculative response model). HTTP 204/empty responses return `[]`.

### Recordings

To download directly to a **new** file, rather than buffering the audio in memory:

```php
$path = Voipano::recordings()->downloadCall(
    'cdr-id', 'call-details-record-id', storage_path('app/private/call.wav')
);
$path = Voipano::recordings()->downloadVoicemail(
    'vm-id', 'default', '1001', storage_path('app/private/message.wav')
);
```

The parent directory must exist, and the destination must not already exist. Save recordings outside the public web root. The Postman example contains distinct `cdr_id` and `call_details_record_id` values despite describing the latter as the same ID; both are required in this SDK until the server contract is confirmed.

### Outgoing webhook setup

```php
Voipano::webhooks()->configure([
    'endpoint_url' => 'https://example.org/webhooks/voipano',
    'secret' => $secret,
    'is_active' => true,
]);
Voipano::webhooks()->route('missed_call', [
    'endpoint_url' => 'https://example.org/webhooks/voipano/missed',
    'is_active' => true,
]);
```

Available event-specific routes: `ringing`, `hangup`, `missed_call`, and `call_details_record`.

### Incoming webhook receiver

The Postman collection does **not** specify how Voipano signs incoming webhook requests. For security, **no inbound route is registered by default**; the built-in `RejectAllVerifier` rejects every request even if enabled.

Once the signing protocol is verified against the server implementation, implement `Voipano\Laravel\Webhooks\Verifier`, configure its class under `voipano.webhooks.verifier`, and then configure:

```dotenv
VOIPANO_WEBHOOK_RECEIVER_ENABLED=true
VOIPANO_WEBHOOK_SECRET=your-verified-shared-secret
```

The default route is `POST /webhooks/voipano`; valid JSON object payloads that pass verification dispatch `Voipano\Laravel\Webhooks\WebhookReceived`. Do not use an accept-all verifier or turn on inbound webhooks until authentication and replay checks have been implemented and tested.

## Errors and reliability

```php
use Voipano\Laravel\Exceptions\ValidationException;

try {
    Voipano::contacts()->create(['primary_number' => '09120000000']);
} catch (ValidationException $e) {
    // $e->statusCode, $e->requestId, $e->errors
}
```

`AuthenticationException` (401), `PermissionException` (403), `NotFoundException` (404), `ValidationException` (422), `RateLimitException` (429), and `ServerException` (5xx) inherit from `ApiException`. Transport and unexpected response errors have separate exception types.

The client generates an `X-Request-Id` and sends `X-Webservice-Service-Id` when selected. HTTP redirects and automatic retries are disabled. In particular, **call and OTP operations must not be retried blindly**: the documentation does not guarantee that `client_ref` prevents duplicate actions.

## Tests

```bash
composer install
composer validate --strict --no-check-publish
composer test
```

Tests use faked HTTP requests and do not initiate real calls. The GitHub Actions matrix covers PHP 8.2–8.5 with compatible Laravel 12/13 versions. Review the real API's response schemas, limits, webhook signature protocol, and recording identifiers before relying on unverified behavior. The supplied Postman collection contains no example responses.

## License

MIT. See [LICENSE](LICENSE).
