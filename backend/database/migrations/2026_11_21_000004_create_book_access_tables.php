<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One row per reader per book: how much they paid and how many pages that opened.
        Schema::create('book_access', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('credit_balance')->default(0);
            $t->unsignedInteger('unlocked_pages')->default(0);
            $t->timestamps();
            $t->unique(['user_id', 'book_id']);
        });

        Schema::create('book_reading_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('last_page')->default(1);
            $t->timestamps();
            $t->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_reading_progress');
        Schema::dropIfExists('book_access');
    }
};
