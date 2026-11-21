<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookAccess extends Model
{
    protected $table = 'book_access';

    protected $fillable = ['user_id', 'book_id', 'credit_balance', 'unlocked_pages'];

    protected $casts = [
        'credit_balance' => 'integer',
        'unlocked_pages' => 'integer',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
