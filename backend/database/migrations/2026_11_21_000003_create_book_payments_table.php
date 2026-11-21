<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('book_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('amount');
            $t->char('currency', 3)->default('TZS');
            $t->string('provider');
            $t->string('provider_ref')->unique();
            $t->string('status')->default('pending');          // pending | confirmed | failed
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_payments');
    }
};
