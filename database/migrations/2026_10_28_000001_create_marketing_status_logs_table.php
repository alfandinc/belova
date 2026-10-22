<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who did what and when on marketing workflow records (content list approval, content plan
 * status changes, design requests). One row per action; the latest row is not overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_status_logs')) {
            return;
        }

        Schema::create('marketing_status_logs', function (Blueprint $table) {
            $table->id();
            $table->string('loggable_type');
            $table->unsignedBigInteger('loggable_id');
            $table->string('action', 50);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['loggable_type', 'loggable_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_status_logs');
    }
};
