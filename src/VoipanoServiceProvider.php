<?php

declare(strict_types=1);

namespace Voipano\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Voipano\Laravel\Http\Controllers\WebhookController;

class VoipanoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/voipano.php', 'voipano');

        $this->app->singleton(Client::class, function ($app): Client {
            $config = $app['config']->get('voipano', []);

            return new Client(
                token: (string) ($config['token'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://voipano.com/api/v1/webservice'),
                serviceId: (string) ($config['service_id'] ?? ''),
                timeout: (int) ($config['timeout'] ?? 30),
                connectTimeout: (int) ($config['connect_timeout'] ?? 10),
            );
        });

        $this->app->alias(Client::class, 'voipano');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/voipano.php' => config_path('voipano.php'),
        ], 'voipano-config');

        if ((bool) config('voipano.webhooks.enabled', false)) {
            Route::middleware(config('voipano.webhooks.middleware', ['api']))
                ->post(trim((string) config('voipano.webhooks.path', 'webhooks/voipano'), '/'), WebhookController::class)
                ->name('voipano.webhooks.receive');
        }
    }
}
