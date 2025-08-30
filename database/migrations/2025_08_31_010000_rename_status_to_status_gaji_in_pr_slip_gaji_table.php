<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->renameColumn('status', 'status_gaji');
        });
    }
    public function down() {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->renameColumn('status_gaji', 'status');
        });
    }
};
