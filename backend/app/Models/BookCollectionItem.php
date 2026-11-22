<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCollectionItem extends Model
{
    protected $table = 'book_collection_items';

    protected $fillable = ['collection_id', 'book_id'];

    protected $casts = [
        'collection_id' => 'integer',
        'book_id' => 'integer',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(BookCollection::class, 'collection_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
