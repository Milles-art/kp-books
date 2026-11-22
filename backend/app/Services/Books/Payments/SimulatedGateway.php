<?php

namespace App\Services\Books\Payments;

use App\Models\BookPayment;
use Illuminate\Http\Request;

/** Demo gateway. Only works when KPBOOK_SIMULATE_PAYMENTS=true. */
class SimulatedGateway implements PaymentGateway
{
    public function initiate(BookPayment $payment): array
    {
        return ['instructions' => 'Demo payment. Confirm it with the simulate-confirm endpoint.'];
    }

    public function parseCallback(Request $request): ?array
    {
        if (! config('kpbook.simulate_payments')) {
            return null;
        }

        // Shared commerce: when a webhook secret is configured, require a valid
        // HMAC over the raw body before trusting any callback.
        $secret = config('kpbook.webhook_secret');
        if ($secret) {
            $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);
            $provided = (string) $request->header('X-Signature', '');
            if (! hash_equals($expected, $provided)) {
                return null;
            }
        }

        $ref = $request->input('provider_ref');
        $status = $request->input('status');

        if (! is_string($ref) || ! in_array($status, ['success', 'failed'], true)) {
            return null;
        }

        return ['provider_ref' => $ref, 'status' => $status];
    }
}
