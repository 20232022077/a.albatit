<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->index(['type', 'status', 'sort_order']);
            $table->fullText(['title', 'excerpt', 'body']);
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropFullText(['title', 'excerpt', 'body']);
            $table->dropIndex(['type', 'status', 'sort_order']);
        });
    }
};
