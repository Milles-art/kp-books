<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    protected $fillable = ['slug', 'name', 'role', 'bio', 'portrait_path', 'location'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function getPortraitUrlAttribute()
    {
        return $this->portrait_path ? asset('storage/' . $this->portrait_path) : null;
    }
}
