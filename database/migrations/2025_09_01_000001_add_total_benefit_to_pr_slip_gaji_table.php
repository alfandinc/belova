<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->decimal('total_benefit', 15, 2)->default(0)->after('benefit_jkm');
        });
    }

    public function down()
    {
        Schema::table('pr_slip_gaji', function (Blueprint $table) {
            $table->dropColumn('total_benefit');
        });
    }
};
