<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookPayment extends Model
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const FAILED = 'failed';
    public const REFUNDED = 'refunded';

    protected $fillable = [
        'user_id', 'book_id', 'amount', 'currency', 'provider',
        'provider_ref', 'status', 'confirmed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
