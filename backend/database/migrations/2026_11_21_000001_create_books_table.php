<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('books', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('subtitle')->nullable();
            $t->string('author');
            $t->text('description')->nullable();
            $t->string('cover_path')->nullable();
            $t->unsignedBigInteger('price_full');              // TZS, whole shillings
            $t->unsignedInteger('total_pages');
            $t->unsignedSmallInteger('free_preview_pages')->default(5);
            $t->unsignedInteger('min_payment')->default(1000); // TZS
            $t->unsignedSmallInteger('unlock_block')->default(5);
            $t->string('status')->default('draft');           // draft | published
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
