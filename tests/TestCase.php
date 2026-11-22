<?php

declare(strict_types=1);

namespace Voipano\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Voipano\Laravel\VoipanoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [VoipanoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('voipano.token', 'test-token');
        $app['config']->set('voipano.service_id', '');
        $app['config']->set('voipano.webhooks.enabled', false);
    }
}
