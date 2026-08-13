<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biography_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biography_id')->constrained('biographies', 'content_item_id')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->longText('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
            $table->index(['biography_id', 'type', 'sort_order']);
        });
    }

    public function down(): void { Schema::dropIfExists('biography_sections'); }
};
