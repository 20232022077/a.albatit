<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('seo_settings')) {
            $old = DB::table('seo_settings')->first();

            if ($old) {
                $now = now();
                $rows = [
                    'general.site_name' => $old->site_title,
                    'general.site_description' => $old->default_meta_description,
                    'seo.default_og_image_id' => $old->default_og_image_id,
                    'seo.twitter_site' => $old->twitter_site,
                    'seo.google_site_verification' => $old->google_site_verification,
                ];

                foreach ($rows as $key => $value) {
                    if (filled($value)) {
                        DB::table('settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
                    }
                }
            }

            Schema::drop('seo_settings');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
