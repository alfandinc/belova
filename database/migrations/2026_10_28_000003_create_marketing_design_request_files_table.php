<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files of a design request: references from the requester and result versions (v1, v2, ...)
 * from the designer. A result can also be a link (e.g. Google Drive) for large files.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_design_request_files')) {
            return;
        }

        Schema::create('marketing_design_request_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_request_id');
            $table->string('kind', 20); // referensi / hasil
            $table->unsignedInteger('version')->nullable();
            $table->string('path')->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime', 150)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['design_request_id', 'kind']);
            $table->foreign('design_request_id')->references('id')->on('marketing_design_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_design_request_files');
    }
};
