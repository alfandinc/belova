<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instagram (professional) accounts connected to the Content Plan for reading post insights.
 * The access token is stored encrypted and refreshed by the system before it expires.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_social_accounts')) {
            return;
        }

        Schema::create('marketing_social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->default('instagram');
            $table->string('external_id', 64);        // Instagram user id
            $table->string('username')->nullable();
            $table->string('brand', 100)->nullable();  // clinic / brand value of the Content Plan (informative)
            $table->text('access_token');              // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('token_refreshed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_social_accounts');
    }
};
