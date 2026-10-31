<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which visit referrals count as Marketing & Sales activity (Admin Panel > Referral Marketing).
 * referral_key '' = rule for the whole referral_type; a non-empty key overrides it for one detail
 * (App\Support\Marketing\ReferralMapping::keySql).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_referral_mappings')) {
            return;
        }

        Schema::create('marketing_referral_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('referral_type', 50);
            $table->string('referral_key', 100)->default('');
            $table->boolean('is_marketing')->default(false);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['referral_type', 'referral_key'], 'marketing_referral_mappings_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_referral_mappings');
    }
};
