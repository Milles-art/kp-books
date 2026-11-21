<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookAccess;

class BookPresenter
{
    public function __construct(private BookAccessService $access)
    {
    }

    public function summary(Book $book, ?BookAccess $access = null, ?int $lastPage = null): array
    {
        return [
            'slug' => $book->slug,
            'title' => $book->title,
            'subtitle' => $book->subtitle,
            'author' => $book->author,
            'description' => $book->description,
            'cover_url' => $book->cover_path ? asset('storage/' . $book->cover_path) : null,
            'price_full' => $book->price_full,
            'currency' => config('kpbook.currency'),
            'total_pages' => $book->total_pages,
            'free_preview_pages' => $book->free_preview_pages,
            'min_payment' => $book->min_payment,
            'unlock_block' => $book->unlock_block,
            'access' => [
                'allowed_pages' => $this->access->allowedPages($book, $access),
                'credit_balance' => $access?->credit_balance ?? 0,
                'remaining_price' => $this->access->remaining($book, $access),
                'last_page' => $lastPage,
            ],
        ];
    }

    /** "What your payment opens" examples shown on the book page. */
    public function options(Book $book, ?BookAccess $access): array
    {
        $remaining = $this->access->remaining($book, $access);
        $amounts = array_values(array_unique(array_filter(
            [$book->min_payment, $book->min_payment * 2, $book->min_payment * 5, $remaining],
            fn ($a) => $a > 0 && $a <= $remaining
        )));
        sort($amounts);

        return array_map(function ($amount) use ($book, $access) {
            $q = $this->access->quote($book, $access, $amount);

            return [
                'amount' => $q['amount'],
                'allowed_pages_after' => $q['allowed_pages_after'],
                'whole_book' => $q['whole_book'],
            ];
        }, $amounts);
    }
}
