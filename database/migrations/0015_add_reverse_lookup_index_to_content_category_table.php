<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_category', function (Blueprint $table) {
            $table->index(['category_id', 'content_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('content_category', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'content_item_id']);
        });
    }
};
