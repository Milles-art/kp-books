<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('books', function (Blueprint $t) {
            $t->string('source_path')->nullable();
            $t->string('source_format', 10)->nullable();
            $t->string('import_status')->default('none'); // none | queued | processing | done | failed
            $t->text('import_error')->nullable();
            $t->unsignedSmallInteger('words_per_page')->default(280);
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $t) {
            $t->dropColumn(['source_path', 'source_format', 'import_status', 'import_error', 'words_per_page']);
        });
    }
};
