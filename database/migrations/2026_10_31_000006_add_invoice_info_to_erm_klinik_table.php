<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact + bank account per klinik, shown in the invoice footer and set from Admin › Klinik Settings.
 * Empty = the invoice keeps its built-in text (Premiere / Belova Skin).
 */
return new class extends Migration
{
    private const COLUMNS = [
        'alamat' => 500,
        'telp' => 100,
        'website' => 255,
        'bank_name' => 100,
        'bank_account_number' => 50,
        'bank_account_name' => 255,
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $column => $length) {
            // Prod schema may drift from local: only add what is missing
            if (!Schema::hasColumn('erm_klinik', $column)) {
                Schema::table('erm_klinik', function (Blueprint $table) use ($column, $length) {
                    $table->string($column, $length)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::COLUMNS) as $column) {
            if (Schema::hasColumn('erm_klinik', $column)) {
                Schema::table('erm_klinik', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
