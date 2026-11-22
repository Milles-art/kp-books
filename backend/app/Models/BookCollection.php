<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCollection extends Model
{
    protected $table = 'book_collections';

    protected $fillable = ['user_id', 'name'];

    protected $casts = ['user_id' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookCollectionItem::class, 'collection_id');
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_collection_items', 'collection_id', 'book_id')
            ->withPivot('id')
            ->withTimestamps();
    }
}
