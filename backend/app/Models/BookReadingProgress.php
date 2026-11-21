<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookReadingProgress extends Model
{
    protected $table = 'book_reading_progress';

    protected $fillable = ['user_id', 'book_id', 'last_page'];

    protected $casts = ['last_page' => 'integer'];
}
