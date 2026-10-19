<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A rental whose period has ended keeps its room "dihuni (lewat masa sewa)" until staff either
 * extends it or confirms the tenant left. checked_out_at records that confirmation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bcl_tr_renter', function (Blueprint $table) {
            if (!Schema::hasColumn('bcl_tr_renter', 'checked_out_at')) {
                $table->dateTime('checked_out_at')->nullable();
            }
            if (!Schema::hasColumn('bcl_tr_renter', 'checked_out_by')) {
                $table->unsignedBigInteger('checked_out_by')->nullable();
            }
        });

        // Existing data: rentals that clearly ended are confirmed as checked out, so only rentals that ended
        // in the last 14 days with nothing after them show up as "lewat masa sewa" for staff to confirm.
        $today = Carbon::today()->format('Y-m-d');
        $cutoff = Carbon::today()->subDays(14)->format('Y-m-d');
        $rows = DB::table('bcl_tr_renter')->whereNull('checked_out_at')->get(['id', 'id_renter', 'room_id', 'tgl_mulai', 'tgl_selesai']);
        $ids = $rows->filter(function ($trx) use ($rows, $today, $cutoff) {
            if ($trx->tgl_selesai > $today) {
                return false;
            }
            if ($trx->tgl_selesai < $cutoff) {
                return true;
            }
            $nextDay = Carbon::parse($trx->tgl_selesai)->addDay()->format('Y-m-d');

            return $rows->contains(fn ($o) => $o->id !== $trx->id && (
                ($o->room_id == $trx->room_id && $o->tgl_mulai >= $trx->tgl_selesai)
                || ($o->id_renter == $trx->id_renter && $o->tgl_mulai >= $trx->tgl_selesai && $o->tgl_mulai <= $nextDay)
            ));
        })->pluck('id');

        foreach ($ids->chunk(500) as $chunk) {
            DB::table('bcl_tr_renter')->whereIn('id', $chunk)->update(['checked_out_at' => DB::raw('tgl_selesai')]);
        }
    }

    public function down(): void
    {
        Schema::table('bcl_tr_renter', function (Blueprint $table) {
            if (Schema::hasColumn('bcl_tr_renter', 'checked_out_by')) {
                $table->dropColumn('checked_out_by');
            }
            if (Schema::hasColumn('bcl_tr_renter', 'checked_out_at')) {
                $table->dropColumn('checked_out_at');
            }
        });
    }
};
