<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->decimal('tunjangan_masa_kerja', 15, 2)->nullable()->after('tunjangan_jabatan');
        });
    }
    public function down() {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->dropColumn('tunjangan_masa_kerja');
        });
    }
};
