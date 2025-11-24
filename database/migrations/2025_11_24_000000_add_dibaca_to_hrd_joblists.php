<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrd_joblists', function (Blueprint $table) {
            $table->unsignedBigInteger('dibaca_by')->nullable()->after('updated_by');
            $table->timestamp('dibaca_at')->nullable()->after('dibaca_by');
            $table->index('dibaca_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrd_joblists', function (Blueprint $table) {
            $table->dropIndex(['dibaca_by']);
            $table->dropColumn(['dibaca_by', 'dibaca_at']);
        });
    }
};
