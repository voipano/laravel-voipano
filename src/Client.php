<?php

declare(strict_types=1);

namespace Voipano\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Voipano\Laravel\Exceptions\ApiException;
use Voipano\Laravel\Exceptions\AuthenticationException;
use Voipano\Laravel\Exceptions\NotFoundException;
use Voipano\Laravel\Exceptions\PermissionException;
use Voipano\Laravel\Exceptions\RateLimitException;
use Voipano\Laravel\Exceptions\ServerException;
use Voipano\Laravel\Exceptions\TransportException;
use Voipano\Laravel\Exceptions\UnexpectedResponseException;
use Voipano\Laravel\Exceptions\ValidationException;
use Voipano\Laravel\Resources\CallReports;
use Voipano\Laravel\Resources\Calls;
use Voipano\Laravel\Resources\ContactGroups;
use Voipano\Laravel\Resources\Contacts;
use Voipano\Laravel\Resources\MusicOnHolds;
use Voipano\Laravel\Resources\Recordings;
use Voipano\Laravel\Resources\Services;
use Voipano\Laravel\Resources\VoicemailInboxes;
use Voipano\Laravel\Resources\Webhooks;

/** API client; withToken(), forService(), and withRequestId() return clones. */
class Client
{
    private ?string $requestId = null;

    public function __construct(
        private string $token,
        private string $baseUrl = 'https://voipano.com/api/v1/webservice',
        private string $serviceId = '',
        private int $timeout = 30,
        private int $connectTimeout = 10,
    ) {
        if ($this->timeout < 1 || $this->connectTimeout < 1) {
            throw new InvalidArgumentException('Timeout values must be positive.');
        }
        if (preg_match('/[\x00-\x20\x7F]/', $this->baseUrl) || !filter_var($this->baseUrl, FILTER_VALIDATE_URL)
            || !in_array(strtolower((string) parse_url($this->baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new InvalidArgumentException('baseUrl must be an absolute HTTP(S) URL.');
        }
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->token = $token;
        return $clone;
    }

    public function forService(string $serviceId): self
    {
        $clone = clone $this;
        $clone->serviceId = $serviceId;
        return $clone;
    }

    public function withRequestId(string $requestId): self
    {
        if (trim($requestId) === '' || preg_match('/[\r\n]/', $requestId)) {
            throw new InvalidArgumentException('Invalid request ID.');
        }
        $clone = clone $this;
        $clone->requestId = $requestId;
        return $clone;
    }

    public function services(): Services
    {
        return new Services($this);
    }

    public function contacts(): Contacts
    {
        return new Contacts($this);
    }

    public function contactGroups(): ContactGroups
    {
        return new ContactGroups($this);
    }

    public function musicOnHolds(): MusicOnHolds
    {
        return new MusicOnHolds($this);
    }

    public function callReports(): CallReports
    {
        return new CallReports($this);
    }

    public function voicemailInboxes(): VoicemailInboxes
    {
        return new VoicemailInboxes($this);
    }

    public function recordings(): Recordings
    {
        return new Recordings($this);
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this);
    }

    public function calls(): Calls
    {
        return new Calls($this);
    }

    /** @return array<string|int,mixed> JSON object/array returned by the API */
    public function request(string $method, string $path, array $parameters = []): array
    {
        $response = $this->send($method, $path, $parameters);
        $this->raiseForStatus($response);
        if ($response->status() === 204 || trim($response->body()) === '') {
            return [];
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new UnexpectedResponseException('Expected a JSON object or array from Voipano.');
        }
        return $json;
    }

    /** Binary response; prefer downloadTo() for large audio files. */
    public function binary(string $path, array $query = []): Response
    {
        $response = $this->send('GET', $path, $query, true);
        $this->raiseForStatus($response);
        $this->assertBinaryResponse($response);
        return $response;
    }

    /**
     * Stream a remote recording into an exclusive new destination file.
     * Never overwrites existing files. Partial/error payloads are deleted.
     */
    public function downloadTo(string $path, string $destination, array $query = []): string
    {
        $directory = dirname($destination);
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new InvalidArgumentException('Download destination directory must exist and be writable.');
        }
        if (file_exists($destination)) {
            throw new InvalidArgumentException('Download destination already exists.');
        }
        $temporary = tempnam($directory, '.voipano-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to allocate temporary download file.');
        }
        try {
            $response = $this->send('GET', $path, $query, true, $temporary);
            if ($response->status() >= 300) {
                $raw = file_get_contents($temporary, false, null, 0, 8192);
                $this->raiseForStatus($response, is_string($raw) ? $raw : null);
            }
            $this->assertBinaryResponse($response);
            // exclusive write: do not clobber a file created since the initial check.
            $output = fopen($destination, 'x');
            if ($output === false) {
                throw new RuntimeException('Download destination could not be created.');
            }
            try {
                $input = fopen($temporary, 'rb');
                if ($input === false) {
                    throw new RuntimeException('Temporary download cannot be read.');
                }
                try {
                    if (stream_copy_to_stream($input, $output) === false) {
                        throw new RuntimeException('Cannot copy downloaded file.');
                    }
                } finally {
                    fclose($input);
                }
            } catch (Throwable $e) {
                fclose($output);
                @unlink($destination);
                throw $e;
            }
            fclose($output);
            return $destination;
        } finally {
            @unlink($temporary);
        }
    }

    private function send(string $method, string $path, array $parameters = [], bool $binary = false, ?string $sink = null): Response
    {
        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException('Unsupported HTTP method.');
        }
        if (trim($this->token) === '') {
            throw new InvalidArgumentException('VOIPANO_API_TOKEN must be configured.');
        }
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '?')) {
            throw new InvalidArgumentException('The API path must be a relative absolute-path without a query.');
        }

        $headers = ['X-Request-Id' => $this->requestId ?? (string) Str::uuid()];
        if (trim($this->serviceId) !== '') {
            $headers['X-Webservice-Service-Id'] = $this->serviceId;
        }
        $pending = Http::withToken($this->token)
            ->withHeaders($headers)
            ->accept($binary ? '*/*' : 'application/json')
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withOptions(['allow_redirects' => false]);

        if ($sink !== null) {
            $pending = $pending->withOptions(['sink' => $sink]);
        }
        $url = $this->baseUrl . $path;
        try {
            return match ($method) {
                'GET' => $pending->get($url, self::query($parameters)),
                'POST' => $pending->asJson()->post($url, $parameters),
                'PUT' => $pending->asJson()->put($url, $parameters),
                'PATCH' => $pending->asJson()->patch($url, $parameters),
                'DELETE' => $pending->delete($url, $parameters),
            };
        } catch (ConnectionException $e) {
            throw new TransportException('Could not connect to Voipano API.', previous: $e);
        }
    }

    /** Omit only null and empty string query values; preserve false, 0 and arrays. */
    private static function query(array $parameters): array
    {
        return array_filter($parameters, static fn ($value) => $value !== null && $value !== '');
    }

    /** Reject JSON/HTML success pages masquerading as an audio download. */
    private function assertBinaryResponse(Response $response): void
    {
        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if ($type === 'application/json' || str_ends_with($type, '+json') || $type === 'text/html') {
            throw new UnexpectedResponseException('Expected audio/binary data but received ' . $type . '.');
        }
    }

    private function raiseForStatus(Response $response, ?string $raw = null): void
    {
        if ($response->status() < 300) {
            return;
        }
        $status = $response->status();
        $body = json_decode($raw ?? $response->body(), true);
        $body = is_array($body) ? $body : [];
        $apiMessage = $body['message'] ?? $body['error'] ?? null;
        $message = is_string($apiMessage) && $apiMessage !== '' ? $apiMessage : "Voipano API returned HTTP {$status}.";
        $requestId = $response->header('X-Request-Id');
        $errors = isset($body['errors']) && is_array($body['errors']) ? $body['errors'] : [];
        $exception = match (true) {
            $status === 401 => AuthenticationException::class,
            $status === 403 => PermissionException::class,
            $status === 404 => NotFoundException::class,
            $status === 422 => ValidationException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => ApiException::class,
        };
        throw new $exception($message, $status, $requestId ?: null, $errors);
    }
}
