<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('job_title')->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable()->index();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['mime_type', 'created_at']);
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status', 'published_at']);
        });

        Schema::create('content_category', function (Blueprint $table) {
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_item_id', 'category_id']);
        });

        Schema::create('content_tag', function (Blueprint $table) {
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_item_id', 'tag_id']);
        });

        Schema::create('content_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->string('collection', 40)->default('default');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['content_item_id', 'media_id', 'collection']);
            $table->index(['content_item_id', 'collection', 'sort_order']);
        });

        Schema::create('books', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('isbn', 20)->nullable()->unique();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->unsignedSmallInteger('pages_count')->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('pdf_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('biographies', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->date('born_on')->nullable();
            $table->date('died_on')->nullable();
            $table->string('birthplace')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_collections', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_items', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('quran_collection_id')->constrained('quran_collections', 'content_item_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_number')->nullable()->index();
            $table->unsignedSmallInteger('ayah_from')->nullable();
            $table->unsignedSmallInteger('ayah_to')->nullable();
            $table->string('reciter')->nullable();
            $table->foreignId('audio_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
            $table->index(['quran_collection_id', 'surah_number']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('presenter')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->timestamps();
        });

        Schema::create('program_episodes', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs', 'content_item_id')->cascadeOnDelete();
            $table->unsignedInteger('episode_number');
            $table->timestamp('aired_at')->nullable()->index();
            $table->foreignId('video_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
            $table->unique(['program_id', 'episode_number']);
        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('speaker')->nullable();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->string('venue')->nullable();
            $table->foreignId('audio_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('video_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reflections', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('surah_number')->nullable()->index();
            $table->unsignedSmallInteger('ayah_from')->nullable();
            $table->unsignedSmallInteger('ayah_to')->nullable();
            $table->timestamps();
        });

        Schema::create('wall_posts', function (Blueprint $table) {
            $table->foreignId('content_item_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section')->default('general');
            $table->string('key');
            $table->json('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['section', 'key']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100)->index();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'site_settings', 'wall_posts', 'reflections', 'lectures', 'program_episodes', 'programs', 'quran_items', 'quran_collections', 'biographies', 'books', 'content_media', 'content_tag', 'content_category', 'content_items', 'media', 'tags', 'categories', 'user_profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
