<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily numbers of a connected Instagram account: total followers (when read that day), new followers,
 * unfollows, account reach and the latest follower demographics. Filled by "Update insight"; the
 * first run back-fills the last 30 days that Instagram keeps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_social_account_stats')) {
            return;
        }

        Schema::create('marketing_social_account_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('social_account_id');
            $table->date('tanggal');
            $table->unsignedBigInteger('followers')->nullable();     // total, only on days it was read
            $table->unsignedInteger('new_followers')->nullable();
            $table->unsignedInteger('unfollows')->nullable();
            $table->unsignedBigInteger('reach')->nullable();         // accounts reached that day
            $table->json('demographics')->nullable();                // {age:{..}, gender:{..}, city:{..}, country:{..}}
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['social_account_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_social_account_stats');
    }
};
