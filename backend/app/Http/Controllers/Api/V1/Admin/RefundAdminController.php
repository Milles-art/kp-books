<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\Books\RefundService;
use Illuminate\Http\Request;

class RefundAdminController extends Controller
{
    public function index()
    {
        $refunds = Refund::with(['payment.book', 'user'])
            ->latest()
            ->get()
            ->map(fn (Refund $refund) => [
                'id' => $refund->id,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status,
                'created_at' => $refund->created_at?->toISOString(),
                'processed_at' => $refund->processed_at?->toISOString(),
                'payment' => [
                    'id' => $refund->payment->id,
                    'provider' => $refund->payment->provider,
                    'provider_ref' => $refund->payment->provider_ref,
                    'status' => $refund->payment->status,
                ],
                'book' => [
                    'slug' => $refund->payment->book->slug,
                    'title' => $refund->payment->book->title,
                ],
                'user' => [
                    'id' => $refund->user->id,
                    'name' => $refund->user->name,
                    'email' => $refund->user->email,
                ],
            ])
            ->values();

        return response()->json(['data' => $refunds]);
    }

    public function process(Request $request, Refund $refund, RefundService $refunds)
    {
        $data = $request->validate(['approve' => ['required', 'boolean']]);

        $refund = $refunds->process($refund, $request->user(), (bool) $data['approve']);

        return response()->json(['data' => [
            'id' => $refund->id,
            'status' => $refund->status,
            'payment_status' => $refund->payment->status,
            'processed_at' => $refund->processed_at?->toISOString(),
        ]]);
    }
}
