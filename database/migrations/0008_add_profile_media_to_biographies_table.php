<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->foreignId('profile_media_id')->nullable()->after('content_item_id')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_media_id');
        });
    }
};
