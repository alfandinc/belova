<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFakturFieldsToFinancePengajuanDanaItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
            // link to erm_fakturbeli if the item is a faktur snapshot
            $table->unsignedBigInteger('fakturbeli_id')->nullable()->after('harga_satuan');
            $table->boolean('is_faktur')->default(false)->after('fakturbeli_id');
            // snapshot of faktur total (for audit/history) — do not write to generated columns
            $table->decimal('harga_total_snapshot', 15, 2)->nullable()->after('is_faktur');

            // optional FK constraint; keep nullable and set null on delete
            $table->foreign('fakturbeli_id')->references('id')->on('erm_fakturbeli')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_pengajuan_dana_item', function (Blueprint $table) {
            if (Schema::hasColumn('finance_pengajuan_dana_item', 'fakturbeli_id')) {
                $table->dropForeign(['fakturbeli_id']);
                $table->dropColumn('fakturbeli_id');
            }
            if (Schema::hasColumn('finance_pengajuan_dana_item', 'is_faktur')) {
                $table->dropColumn('is_faktur');
            }
            if (Schema::hasColumn('finance_pengajuan_dana_item', 'harga_total_snapshot')) {
                $table->dropColumn('harga_total_snapshot');
            }
        });
    }
}
