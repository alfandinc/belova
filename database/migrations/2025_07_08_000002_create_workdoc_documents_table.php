<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workdoc_documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type');
            $table->bigInteger('file_size');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('folder_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('division_id')->nullable();
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->foreign('folder_id')->references('id')->on('workdoc_folders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('division_id')->references('id')->on('hrd_division')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workdoc_documents');
    }
};
