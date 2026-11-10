<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Odontogram change history (rekam medis: every change traceable to who / when / what).
 * One row per save that changed the odontogram of a visit; rows are never edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('erm_odontogram_riwayat')) {
            return;
        }

        Schema::create('erm_odontogram_riwayat', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id', 191)->index();
            $table->string('pasien_id', 20)->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->json('perubahan');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_odontogram_riwayat');
    }
};
