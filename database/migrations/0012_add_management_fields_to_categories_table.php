<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('image_media_id')->nullable()->after('description')->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('sort_order')->index();
            $table->json('meta')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_media_id');
            $table->dropColumn(['is_active', 'meta']);
        });
    }
};
