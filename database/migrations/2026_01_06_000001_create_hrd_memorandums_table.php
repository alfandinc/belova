<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hrd_memorandums', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('perihal');
            $table->unsignedBigInteger('dari_division_id')->nullable();
            $table->string('kepada')->nullable();
            $table->longText('isi')->nullable(); // Summernote HTML content
            $table->unsignedBigInteger('klinik_id')->nullable();
            $table->unsignedBigInteger('user_id'); // creator
            $table->string('status', 50)->default('draft');
            $table->timestamps();

            // Indexes helpful for Datatable filters
            $table->index('tanggal');
            $table->index('status');

            // Foreign keys
            $table->foreign('dari_division_id')
                ->references('id')->on('hrd_division')
                ->onDelete('set null');

            $table->foreign('klinik_id')
                ->references('id')->on('erm_klinik')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hrd_memorandums');
    }
};
