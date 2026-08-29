<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->index()->after('is_featured');
        });

        // wall_posts.is_pinned already carries real pinned posts — this
        // generalizes pinning to every content type, so existing pins must
        // survive the move onto the shared column.
        if (Schema::hasColumn('wall_posts', 'is_pinned')) {
            DB::table('content_items')
                ->join('wall_posts', 'wall_posts.content_item_id', '=', 'content_items.id')
                ->where('wall_posts.is_pinned', true)
                ->update(['content_items.is_pinned' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropIndex(['is_pinned']);
            $table->dropColumn('is_pinned');
        });
    }
};
