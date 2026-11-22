<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_collection_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('collection_id')->constrained('book_collections')->cascadeOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['collection_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_collection_items');
    }
};
