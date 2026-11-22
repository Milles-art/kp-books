<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained('book_payments')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('amount');
            $t->string('reason');
            $t->string('status')->default('requested');       // requested | approved | rejected | processed
            $t->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id']);
            $t->unique(['payment_id']);                       // one refund per payment
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
