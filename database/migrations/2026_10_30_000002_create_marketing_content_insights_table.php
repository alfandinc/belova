<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily snapshot of a published post's numbers (one row per content, platform and day), read from the
 * platform API when "Update insight" is pressed. The growth per day comes from consecutive rows.
 *
 * Index names are given explicitly: the generated unique name is longer than MySQL's 64 characters.
 * A first run that failed on that index left the table without its indexes, so they are (re)added here.
 */
return new class extends Migration
{
    private const UNIQUE = 'mci_plan_platform_tanggal_unique';
    private const TANGGAL = 'mci_tanggal_index';

    public function up(): void
    {
        if (!Schema::hasTable('marketing_content_insights')) {
            Schema::create('marketing_content_insights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('content_plan_id');
                $table->string('platform', 20)->default('instagram');
                $table->unsignedBigInteger('social_account_id')->nullable();
                $table->string('media_id', 64)->nullable();
                $table->date('tanggal');
                $table->unsignedBigInteger('views')->nullable();
                $table->unsignedBigInteger('reach')->nullable();
                $table->unsignedBigInteger('likes')->nullable();
                $table->unsignedBigInteger('comments')->nullable();
                $table->unsignedBigInteger('saves')->nullable();
                $table->unsignedBigInteger('shares')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('fetched_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('marketing_content_insights', function (Blueprint $table) {
            if (!Schema::hasIndex('marketing_content_insights', self::UNIQUE)) {
                $table->unique(['content_plan_id', 'platform', 'tanggal'], self::UNIQUE);
            }
            if (!Schema::hasIndex('marketing_content_insights', self::TANGGAL)) {
                $table->index('tanggal', self::TANGGAL);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_content_insights');
    }
};
