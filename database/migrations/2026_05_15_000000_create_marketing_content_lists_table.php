<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_content_lists', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->json('brand')->nullable();
            $table->json('platform');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->json('jenis_konten');
            $table->string('konten_pilar')->nullable();
            $table->string('link_referensi')->nullable();
            $table->text('catatan')->nullable();
            $table->string('approval_status')->default('Pending');
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('scheduled_plan_id')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('scheduled_plan_id')->references('id')->on('marketing_content_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_content_lists');
    }
};