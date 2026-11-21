<?php

namespace App\Jobs\Books;

use App\Models\Book;
use App\Services\Books\BookImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportBookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;   // scanned PDFs can take a while
    public int $tries = 1;

    public function __construct(public int $bookId)
    {
    }

    public function handle(BookImporter $importer): void
    {
        $book = Book::find($this->bookId);

        if ($book) {
            $importer->import($book);
        }
    }
}
