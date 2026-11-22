<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Models\BookPayment;
use App\Services\Books\RefundService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function store(Request $request, BookPayment $payment, RefundService $refunds)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $refund = $refunds->request($payment, $data['reason']);

        return response()->json(['data' => [
            'id' => $refund->id,
            'status' => $refund->status,
            'amount' => $refund->amount,
        ]], 201);
    }
}
