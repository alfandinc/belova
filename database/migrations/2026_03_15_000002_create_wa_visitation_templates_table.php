<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wa_visitation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_session_id')->constrained('wa_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('klinik_id')->nullable()->unique();
            $table->longText('template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('wa_session_id');
            $table->foreign('klinik_id')->references('id')->on('erm_klinik')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_visitation_templates');
    }
};