<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'subtitle', 'author', 'description', 'cover_path',
        'price_full', 'total_pages', 'free_preview_pages', 'min_payment',
        'unlock_block', 'status', 'published_at',
        'source_path', 'source_format', 'import_status', 'import_error', 'words_per_page',
    ];

    protected $casts = [
        'price_full' => 'integer',
        'total_pages' => 'integer',
        'free_preview_pages' => 'integer',
        'min_payment' => 'integer',
        'unlock_block' => 'integer',
        'words_per_page' => 'integer',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function pages(): HasMany
    {
        return $this->hasMany(BookPage::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(BookAccess::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookPayment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
