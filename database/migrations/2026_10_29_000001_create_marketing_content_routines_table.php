<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Konten rutin": content made once and uploaded on a fixed schedule (e.g. "We Are Open" every day 09:00).
 * It appears on the Content Plan board on each scheduled day; only the daily upload is recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_content_routines')) {
            return;
        }

        Schema::create('marketing_content_routines', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->json('brand')->nullable();
            $table->json('platform')->nullable();
            $table->json('jenis_konten')->nullable();
            $table->time('jam')->default('09:00:00');
            // ISO days of the week it is uploaded: 1 = Senin … 7 = Minggu
            $table->json('days');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable(); // who uploads it
            $table->string('asset_link', 1000)->nullable();        // final file / Drive folder
            $table->text('caption')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'start_date']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_content_routines');
    }
};
