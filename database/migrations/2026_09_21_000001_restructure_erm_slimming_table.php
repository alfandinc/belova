<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_slimming', function (Blueprint $table) {
            $table->dropForeign(['riwayat_tindakan_id']);
            $table->dropIndex(['riwayat_tindakan_id']);
            $table->dropColumn([
                'riwayat_tindakan_id',
                'target_weight',
                'weight_control',
                'muscle_fat_weight',
                'muscle_fat_muscle',
                'muscle_fat_body_fat_mass',
                'obesity_bmi',
                'obesity_analysis',
                'obesity_eval_bmi',
                'obesity_eval',
                'pbf',
                'subcutaneous_fat',
                'skeletal_muscle',
                'research_basal_metabolic_rate',
                'visceral_fat_level',
            ]);
        });

        Schema::table('erm_slimming', function (Blueprint $table) {
            $table->unsignedInteger('usia')->nullable()->after('dokter_id');
            $table->decimal('base_weight', 10, 2)->nullable()->after('bb');
            $table->decimal('base_fat', 10, 2)->nullable()->after('base_weight');
            $table->decimal('base_visceral_fat', 10, 2)->nullable()->after('base_fat');
            $table->decimal('base_kcal', 10, 2)->nullable()->after('base_visceral_fat');
            $table->decimal('base_bmi', 10, 2)->nullable()->after('base_kcal');
            $table->unsignedInteger('base_body_age')->nullable()->after('base_bmi');
            $table->decimal('lingkar_paha_kanan', 10, 2)->nullable()->after('lingkar_lengan_kiri');
            $table->decimal('lingkar_paha_kiri', 10, 2)->nullable()->after('lingkar_paha_kanan');
        });
    }

    public function down(): void
    {
        Schema::table('erm_slimming', function (Blueprint $table) {
            $table->dropColumn([
                'usia',
                'base_weight',
                'base_fat',
                'base_visceral_fat',
                'base_kcal',
                'base_bmi',
                'base_body_age',
                'lingkar_paha_kanan',
                'lingkar_paha_kiri',
            ]);
        });

        Schema::table('erm_slimming', function (Blueprint $table) {
            $table->unsignedBigInteger('riwayat_tindakan_id')->nullable()->after('dokter_id');
            $table->decimal('target_weight', 10, 2)->nullable()->after('bb');
            $table->decimal('weight_control', 10, 2)->nullable()->after('target_weight');
            $table->decimal('muscle_fat_weight', 10, 2)->nullable()->after('lingkar_lengan_kiri');
            $table->decimal('muscle_fat_muscle', 10, 2)->nullable()->after('muscle_fat_weight');
            $table->decimal('muscle_fat_body_fat_mass', 10, 2)->nullable()->after('muscle_fat_muscle');
            $table->decimal('obesity_bmi', 10, 2)->nullable()->after('muscle_fat_body_fat_mass');
            $table->string('obesity_analysis')->nullable()->after('obesity_bmi');
            $table->decimal('obesity_eval_bmi', 10, 2)->nullable()->after('obesity_analysis');
            $table->string('obesity_eval')->nullable()->after('obesity_eval_bmi');
            $table->decimal('pbf', 10, 2)->nullable()->after('obesity_eval');
            $table->string('subcutaneous_fat')->nullable()->after('pbf');
            $table->string('skeletal_muscle')->nullable()->after('subcutaneous_legs');
            $table->decimal('research_basal_metabolic_rate', 10, 2)->nullable()->after('skeletal_legs');
            $table->decimal('visceral_fat_level', 10, 2)->nullable()->after('research_basal_metabolic_rate');

            $table->foreign('riwayat_tindakan_id')->references('id')->on('erm_riwayat_tindakan')->nullOnDelete();
            $table->index('riwayat_tindakan_id');
        });
    }
};