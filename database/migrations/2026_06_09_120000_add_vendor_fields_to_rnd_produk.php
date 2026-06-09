<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->foreignId('produsen_vendor_id')->nullable()->after('brand_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('kemasan_primer_vendor_id')->nullable()->after('kemasan_sekunder_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('kemasan_sekunder_vendor_id')->nullable()->after('kemasan_primer_vendor_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('desain_kemasan_primer_id')->nullable()->after('kemasan_sekunder_vendor_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('desain_kemasan_sekunder_id')->nullable()->after('desain_kemasan_primer_id')->constrained('rnd_master_vendor')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rnd_produk', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desain_kemasan_sekunder_id');
            $table->dropConstrainedForeignId('desain_kemasan_primer_id');
            $table->dropConstrainedForeignId('kemasan_sekunder_vendor_id');
            $table->dropConstrainedForeignId('kemasan_primer_vendor_id');
            $table->dropConstrainedForeignId('produsen_vendor_id');
        });
    }
};