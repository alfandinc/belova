<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bcl_extra_bed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bcl_extra_bed_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extra_bed_asset_id')->constrained('bcl_extra_bed_assets')->cascadeOnDelete();
            $table->foreignId('extra_rent_id')->constrained('bcl_extra_rent')->cascadeOnDelete();
            $table->date('assigned_from');
            $table->date('assigned_until');
            $table->timestamps();
        });

        DB::table('bcl_extra_bed_assets')->insert([
            [
                'asset_code' => 'EB-01',
                'notes' => 'Default tracked extra bed',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'asset_code' => 'EB-02',
                'notes' => 'Default tracked extra bed',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'asset_code' => 'EB-03',
                'notes' => 'Default tracked extra bed',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_extra_bed_assignments');
        Schema::dropIfExists('bcl_extra_bed_assets');
    }
};