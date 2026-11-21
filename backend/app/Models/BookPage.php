<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookPage extends Model
{
    public $timestamps = false;

    protected $fillable = ['book_id', 'page_no', 'content'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
