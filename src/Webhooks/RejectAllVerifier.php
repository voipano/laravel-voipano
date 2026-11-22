<?php

declare(strict_types=1);

namespace Voipano\Laravel\Webhooks;

use Illuminate\Http\Request;

/** Safe default until the real Voipano webhook signing protocol is documented. */
class RejectAllVerifier implements Verifier
{
    public function verify(Request $request, string $secret): bool
    {
        return false;
    }
}
