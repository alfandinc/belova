<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_slimming', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->string('pasien_id', 6)->nullable();
            $table->foreignId('dokter_id')->nullable()->constrained('erm_dokters')->nullOnDelete();
            $table->unsignedBigInteger('riwayat_tindakan_id')->nullable();
            $table->decimal('tb', 10, 2)->nullable();
            $table->decimal('bb', 10, 2)->nullable();

            $table->decimal('target_weight', 10, 2)->nullable();
            $table->decimal('weight_control', 10, 2)->nullable();
            $table->decimal('lingkar_perut', 10, 2)->nullable();
            $table->decimal('lingkar_lengan_kanan', 10, 2)->nullable();
            $table->decimal('lingkar_lengan_kiri', 10, 2)->nullable();
            $table->decimal('muscle_fat_weight', 10, 2)->nullable();
            $table->decimal('muscle_fat_muscle', 10, 2)->nullable();
            $table->decimal('muscle_fat_body_fat_mass', 10, 2)->nullable();
            $table->decimal('obesity_bmi', 10, 2)->nullable();
            $table->string('obesity_analysis')->nullable();
            $table->decimal('obesity_eval_bmi', 10, 2)->nullable();
            $table->string('obesity_eval')->nullable();
            $table->decimal('pbf', 10, 2)->nullable();
            $table->string('subcutaneous_fat')->nullable();
            $table->decimal('subcutaneous_whole_body', 10, 2)->nullable();
            $table->decimal('subcutaneous_trunk', 10, 2)->nullable();
            $table->decimal('subcutaneous_arms', 10, 2)->nullable();
            $table->decimal('subcutaneous_legs', 10, 2)->nullable();
            $table->string('skeletal_muscle')->nullable();
            $table->decimal('skeletal_whole_body', 10, 2)->nullable();
            $table->decimal('skeletal_trunk', 10, 2)->nullable();
            $table->decimal('skeletal_arms', 10, 2)->nullable();
            $table->decimal('skeletal_legs', 10, 2)->nullable();
            $table->decimal('research_basal_metabolic_rate', 10, 2)->nullable();
            $table->decimal('visceral_fat_level', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->cascadeOnDelete();
            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->nullOnDelete();
            $table->foreign('riwayat_tindakan_id')->references('id')->on('erm_riwayat_tindakan')->nullOnDelete();

            $table->index('pasien_id');
            $table->index('dokter_id');
            $table->index('riwayat_tindakan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erm_slimming');
    }
};