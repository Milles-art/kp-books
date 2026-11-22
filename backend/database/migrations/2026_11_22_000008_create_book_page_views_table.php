<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_page_views', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('page');
            $t->string('device_id')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('viewed_at');
            $t->index(['user_id', 'viewed_at']);
            $t->index(['book_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_page_views');
    }
};
