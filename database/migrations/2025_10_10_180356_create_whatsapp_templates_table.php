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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Template identifier (e.g., 'visitation', 'appointment_reminder')
            $table->string('name'); // Human readable name
            $table->text('description')->nullable(); // Template description
            $table->longText('content'); // Template message content
            $table->json('variables')->nullable(); // Available variables for this template
            $table->boolean('is_active')->default(true); // Whether template is active
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
