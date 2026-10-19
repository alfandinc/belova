<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Inform consent text, the most specific one wins: the tindakan's own (erm_tindakan.consent_content), else the
    // text of the kategori chosen for it (consent_kategori_id -> erm_kategori_tindakan.consent_content), else the
    // generic text. HTML with {tindakan} / {klinik} placeholders. The tindakan texts are filled from the old
    // per-tindakan templates by `php artisan inform-consent:import`.
    // The signed consent's "Catatan Tambahan" is kept as data too (it was only printed in the PDF).
    public function up(): void
    {
        if (!Schema::hasColumn('erm_tindakan', 'consent_content')) {
            Schema::table('erm_tindakan', function (Blueprint $table) {
                $table->longText('consent_content')->nullable()->after('deskripsi');
            });
        }
        if (!Schema::hasColumn('erm_tindakan', 'consent_kategori_id')) {
            Schema::table('erm_tindakan', function (Blueprint $table) {
                $table->unsignedBigInteger('consent_kategori_id')->nullable()->after('consent_content');
                $table->index('consent_kategori_id');
            });
        }
        if (!Schema::hasColumn('erm_kategori_tindakan', 'consent_content')) {
            Schema::table('erm_kategori_tindakan', function (Blueprint $table) {
                $table->longText('consent_content')->nullable()->after('nama');
            });
        }
        if (!Schema::hasColumn('erm_inform_consent', 'notes')) {
            Schema::table('erm_inform_consent', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('file_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('erm_inform_consent', 'notes')) {
            Schema::table('erm_inform_consent', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
        if (Schema::hasColumn('erm_kategori_tindakan', 'consent_content')) {
            Schema::table('erm_kategori_tindakan', function (Blueprint $table) {
                $table->dropColumn('consent_content');
            });
        }
        if (Schema::hasColumn('erm_tindakan', 'consent_kategori_id')) {
            Schema::table('erm_tindakan', function (Blueprint $table) {
                $table->dropIndex(['consent_kategori_id']);
                $table->dropColumn('consent_kategori_id');
            });
        }
        if (Schema::hasColumn('erm_tindakan', 'consent_content')) {
            Schema::table('erm_tindakan', function (Blueprint $table) {
                $table->dropColumn('consent_content');
            });
        }
    }
};
