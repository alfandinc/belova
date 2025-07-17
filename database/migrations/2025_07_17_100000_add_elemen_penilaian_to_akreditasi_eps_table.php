<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('akreditasi_eps', function (Blueprint $table) {
            $table->string('elemen_penilaian')->nullable()->after('name');
        });
    }
    public function down() {
        Schema::table('akreditasi_eps', function (Blueprint $table) {
            $table->dropColumn('elemen_penilaian');
        });
    }
};
