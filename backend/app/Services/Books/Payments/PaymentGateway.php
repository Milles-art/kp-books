<?php

namespace App\Services\Books\Payments;

use App\Models\BookPayment;
use Illuminate\Http\Request;

/**
 * Keep the real provider (Selcom, AzamPay, ClickPesa, etc.) behind this interface
 * so it can be swapped without touching the access rules.
 */
interface PaymentGateway
{
    /** Start the payment with the provider. Returns data the client shows to the reader. */
    public function initiate(BookPayment $payment): array;

    /**
     * Verify and read a provider callback. Return null if it is not authentic.
     * Otherwise return ['provider_ref' => string, 'status' => 'success'|'failed'].
     */
    public function parseCallback(Request $request): ?array;
}
