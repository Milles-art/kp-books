<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'title' => Str::title($title),
            'author' => 'Demo author',
            'description' => fake()->sentence(),
            'price_full' => 8000,
            'total_pages' => 100,
            'free_preview_pages' => 5,
            'min_payment' => 1000,
            'unlock_block' => 5,
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
