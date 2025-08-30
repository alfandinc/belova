<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->integer('total_hari_scheduled')->nullable()->after('bulan');
            $table->integer('total_hari_masuk')->nullable()->after('total_hari_scheduled');
        });
    }
    public function down() {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->dropColumn(['total_hari_scheduled', 'total_hari_masuk']);
        });
    }
};
