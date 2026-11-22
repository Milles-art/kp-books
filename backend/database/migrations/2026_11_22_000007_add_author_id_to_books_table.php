<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('books', function (Blueprint $t) {
            $t->foreignId('author_id')->nullable()->after('author')->constrained('authors')->nullOnDelete();
        });

        // Link existing books to authors by comparing their author strings.
        DB::table('authors')->chunkById(100, function ($authors) {
            foreach ($authors as $author) {
                $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($author->name)), '-');

                $bookIds = DB::table('books')
                    ->whereNull('author_id')
                    ->get(['id', 'author'])
                    ->filter(function ($book) use ($author, $slug) {
                        $name = strtolower(trim((string) $book->author));

                        return $name === strtolower(trim($author->name))
                            || trim(preg_replace('/[^a-z0-9]+/', '-', $name), '-') === $slug;
                    })
                    ->pluck('id')
                    ->all();

                if ($bookIds !== []) {
                    DB::table('books')->whereIn('id', $bookIds)->update(['author_id' => $author->id]);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('books') || ! Schema::hasColumn('books', 'author_id')) {
            return;
        }

        Schema::table('books', function (Blueprint $t) {
            $t->dropForeign(['author_id']);
            $t->dropColumn('author_id');
        });
    }
};
