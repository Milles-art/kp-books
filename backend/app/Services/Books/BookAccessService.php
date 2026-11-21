<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookAccess;
use App\Models\BookPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single place that decides how many pages a reader may see.
 * Pages unlock in order from page 1. Money is whole TZS, so all maths is integer maths.
 */
class BookAccessService
{
    /** Pages that a given total credit opens (never above total_pages). */
    public function pagesForCredit(Book $book, int $credit): int
    {
        if ($credit >= $book->price_full) {
            return $book->total_pages;
        }

        $raw = intdiv($credit * $book->total_pages, $book->price_full);
        $block = max(1, $book->unlock_block);

        return min($book->total_pages, intdiv($raw, $block) * $block);
    }

    public function accessFor(?User $user, Book $book): ?BookAccess
    {
        if (! $user) {
            return null;
        }

        return BookAccess::where('user_id', $user->id)->where('book_id', $book->id)->first();
    }

    /** Highest page the reader may open: free preview or paid pages, whichever is larger. */
    public function allowedPages(Book $book, ?BookAccess $access): int
    {
        $unlocked = $access?->unlocked_pages ?? 0;

        return min($book->total_pages, max($book->free_preview_pages, $unlocked));
    }

    public function remaining(Book $book, ?BookAccess $access): int
    {
        return max(0, $book->price_full - ($access?->credit_balance ?? 0));
    }

    /** What would happen if the reader paid $amount now. Does not write anything. */
    public function quote(Book $book, ?BookAccess $access, int $amount): array
    {
        $remaining = $this->remaining($book, $access);
        $amount = min($amount, $remaining);
        $minimum = min($book->min_payment, $remaining);
        $credit = ($access?->credit_balance ?? 0) + $amount;
        $unlocked = max($access?->unlocked_pages ?? 0, $this->pagesForCredit($book, $credit));

        return [
            'valid' => $remaining > 0 && $amount > 0 && $amount >= $minimum,
            'amount' => $amount,
            'minimum' => $minimum,
            'remaining_price' => $remaining,
            'allowed_pages_after' => min($book->total_pages, max($book->free_preview_pages, $unlocked)),
            'whole_book' => $unlocked >= $book->total_pages,
        ];
    }

    /**
     * Credit a confirmed payment. Safe to call twice for the same payment (webhooks retry).
     * unlocked_pages never goes down, even if the book price changes later.
     */
    public function applyConfirmedPayment(BookPayment $payment): BookAccess
    {
        return DB::transaction(function () use ($payment) {
            $payment = BookPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $book = Book::findOrFail($payment->book_id);

            BookAccess::firstOrCreate(
                ['user_id' => $payment->user_id, 'book_id' => $payment->book_id],
                ['credit_balance' => 0, 'unlocked_pages' => 0]
            );
            $access = BookAccess::where('user_id', $payment->user_id)
                ->where('book_id', $payment->book_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === BookPayment::CONFIRMED) {
                return $access;
            }

            $access->credit_balance += $payment->amount;
            $access->unlocked_pages = max(
                $access->unlocked_pages,
                $this->pagesForCredit($book, $access->credit_balance)
            );
            $access->save();

            $payment->update(['status' => BookPayment::CONFIRMED, 'confirmed_at' => now()]);

            return $access;
        });
    }
}
