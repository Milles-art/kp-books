<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('page_no');
            $t->longText('content');
            $t->unique(['book_id', 'page_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_pages');
    }
};
