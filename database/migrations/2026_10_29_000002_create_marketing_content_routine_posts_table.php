<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per day a routine content was uploaded or skipped. A scheduled day without a row is
 * "belum upload" (or "terlewat" once the upload time has passed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_content_routine_posts')) {
            return;
        }

        Schema::create('marketing_content_routine_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained('marketing_content_routines')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 20); // posted / skipped
            $table->json('link_publikasi')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->unique(['routine_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_content_routine_posts');
    }
};
