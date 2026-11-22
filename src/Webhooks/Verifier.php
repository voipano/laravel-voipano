<?php

declare(strict_types=1);

namespace Voipano\Laravel\Webhooks;

use Illuminate\Http\Request;

interface Verifier
{
    /** Verify authenticity using the ACTUAL server-side signature contract. */
    public function verify(Request $request, string $secret): bool;
}
