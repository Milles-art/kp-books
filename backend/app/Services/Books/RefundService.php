<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookAccess;
use App\Models\BookPayment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Refund policy (Kipanya to confirm before production):
 * - only confirmed payments can be refunded;
 * - only inside the configured window (default 24h);
 * - one refund per payment.
 *
 * Pages unlocked by a refunded payment are rolled back, but never below the
 * book's free preview. Other confirmed top-ups are untouched.
 */
class RefundService
{
    public function eligibility(BookPayment $payment, ?Refund $ignore = null): array
    {
        if ($payment->status !== BookPayment::CONFIRMED) {
            return ['eligible' => false, 'reason' => 'Only confirmed payments can be refunded.'];
        }

        if (! $payment->confirmed_at || $payment->confirmed_at->lt(now()->subHours((int) config('kpbook.refund_window_hours', 24)))) {
            return ['eligible' => false, 'reason' => 'The refund window for this payment has closed.'];
        }

        $existing = Refund::where('payment_id', $payment->id);
        if ($ignore) {
            $existing->where('id', '!=', $ignore->id);
        }
        if ($existing->exists()) {
            return ['eligible' => false, 'reason' => 'This payment has already been refunded.'];
        }

        return ['eligible' => true, 'reason' => null];
    }

    public function request(BookPayment $payment, string $reason): Refund
    {
        $check = $this->eligibility($payment);
        abort_unless($check['eligible'], 422, $check['reason']);

        return Refund::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
            'reason' => $reason,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function process(Refund $refund, ?User $admin, bool $approve): Refund
    {
        abort_unless($refund->status === Refund::STATUS_REQUESTED, 422, 'This refund has already been handled.');

        if (! $approve) {
            $refund->update(['status' => Refund::STATUS_REJECTED, 'admin_id' => $admin?->id]);
            return $refund->refresh();
        }

        DB::transaction(function () use ($refund, $admin) {
            $payment = BookPayment::whereKey($refund->payment_id)->lockForUpdate()->firstOrFail();
            $check = $this->eligibility($payment, $refund);
            abort_unless($check['eligible'], 422, $check['reason']);

            $payment->update(['status' => BookPayment::REFUNDED]);
            $refund->update([
                'status' => Refund::STATUS_PROCESSED,
                'admin_id' => $admin?->id,
                'processed_at' => now(),
            ]);

            $this->recomputeAccess($payment->user, $payment->book);
        });

        return $refund->refresh();
    }

    /** Rebuild the access record from remaining confirmed payments. */
    private function recomputeAccess(User $user, Book $book): void
    {
        $credit = (int) BookPayment::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', BookPayment::CONFIRMED)
            ->sum('amount');

        $access = BookAccess::firstOrCreate(
            ['user_id' => $user->id, 'book_id' => $book->id],
            ['credit_balance' => 0, 'unlocked_pages' => 0]
        );

        $access->credit_balance = $credit;
        $access->unlocked_pages = max(
            (int) $book->free_preview_pages,
            app(BookAccessService::class)->pagesForCredit($book, $credit)
        );
        $access->save();
    }
}
