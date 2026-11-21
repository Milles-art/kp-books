<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

/** Three placeholder books for the presentation. Prices are demo numbers. */
class BookDemoSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            ['slug' => 'safari-ya-mafanikio', 'title' => 'Safari ya Mafanikio', 'subtitle' => 'A short guide to starting small', 'pages' => 100, 'price' => 8000],
            ['slug' => 'hekima-za-mtaani', 'title' => 'Hekima za Mtaani', 'subtitle' => 'Street wisdom, retold', 'pages' => 200, 'price' => 15000],
            ['slug' => 'kesho-yetu', 'title' => 'Kesho Yetu', 'subtitle' => 'A novel of two cities', 'pages' => 350, 'price' => 25000],
        ];

        foreach ($books as $b) {
            $book = Book::updateOrCreate(['slug' => $b['slug']], [
                'title' => $b['title'],
                'subtitle' => $b['subtitle'],
                'author' => 'Demo author',
                'description' => 'Demo book for the KP Book presentation.',
                'price_full' => $b['price'],
                'total_pages' => $b['pages'],
                'free_preview_pages' => 5,
                'min_payment' => 1000,
                'unlock_block' => 5,
                'status' => 'published',
                'published_at' => now(),
            ]);

            $book->pages()->delete();
            $rows = [];
            for ($n = 1; $n <= $b['pages']; $n++) {
                $rows[] = [
                    'book_id' => $book->id,
                    'page_no' => $n,
                    'content' => "Demo text for page {$n} of {$b['title']}.",
                ];
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                $book->pages()->insert($chunk);
            }
        }
    }
}
