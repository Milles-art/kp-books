<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookPayment;
use App\Services\Books\BookAccessService;
use App\Services\Books\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookPaymentController extends Controller
{
    private const PROVIDERS = ['mpesa', 'airtel', 'halopesa', 'mixx', 'card'];

    /** Preview: what would this amount open? Writes nothing. */
    public function quote(Request $request, Book $book, BookAccessService $service)
    {
        abort_unless($book->status === 'published', 404);
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1']]);

        return response()->json([
            'data' => $service->quote($book, $service->accessFor($request->user(), $book), (int) $data['amount']),
        ]);
    }

    /** Start a payment. Pages open only after the provider confirms it. */
    public function store(Request $request, Book $book, BookAccessService $service, PaymentGateway $gateway)
    {
        abort_unless($book->status === 'published', 404);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'provider' => ['required', 'in:' . implode(',', self::PROVIDERS)],
        ]);

        $quote = $service->quote($book, $service->accessFor($request->user(), $book), (int) $data['amount']);

        if (! $quote['valid']) {
            return response()->json([
                'message' => $quote['remaining_price'] === 0
                    ? 'You already own the whole book.'
                    : 'The minimum payment is ' . $quote['minimum'] . ' TZS.',
                'code' => 'INVALID_AMOUNT',
            ], 422);
        }

        $payment = BookPayment::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'amount' => $quote['amount'],
            'currency' => config('kpbook.currency'),
            'provider' => $data['provider'],
            'provider_ref' => (string) Str::uuid(),
            'status' => BookPayment::PENDING,
        ]);

        return response()->json([
            'data' => [
                'id' => $payment->id,
                'provider_ref' => $payment->provider_ref,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'provider_instructions' => $gateway->initiate($payment),
            ],
        ], 201);
    }

    /** Provider webhook. Public route; the gateway must verify the signature. */
    public function callback(Request $request, string $provider, BookAccessService $service, PaymentGateway $gateway)
    {
        $result = $gateway->parseCallback($request);
        if (! $result) {
            return response()->json(['message' => 'Invalid callback.'], 400);
        }

        $payment = BookPayment::where('provider_ref', $result['provider_ref'])->first();
        if (! $payment) {
            return response()->json(['message' => 'Unknown payment.'], 404);
        }

        if ($result['status'] === 'success') {
            $service->applyConfirmedPayment($payment);
        } elseif ($payment->status === BookPayment::PENDING) {
            $payment->update(['status' => BookPayment::FAILED]);
        }

        return response()->json(['ok' => true]);
    }

    /** Demo only: lets the front end confirm a payment without a real provider. */
    public function simulateConfirm(Request $request, BookPayment $payment, BookAccessService $service)
    {
        abort_unless(config('kpbook.simulate_payments'), 404);
        abort_unless($payment->user_id === $request->user()->id, 403);

        $access = $service->applyConfirmedPayment($payment);

        return response()->json(['data' => [
            'status' => 'confirmed',
            'allowed_pages' => $service->allowedPages($payment->book, $access),
        ]]);
    }
}
