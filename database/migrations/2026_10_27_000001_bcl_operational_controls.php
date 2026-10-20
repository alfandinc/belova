<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kos BCL operational controls:
 * - payment method + proof per journal line (tunai / transfer / QRIS / EDC)
 * - cancelled bookings
 * - room check-in / check-out inspections
 * - audit log of corrections and deletions
 * - "bcl.supervisor" permission (delete, refund, corrections), given to Admin
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bcl_fin_jurnal', function (Blueprint $table) {
            if (!Schema::hasColumn('bcl_fin_jurnal', 'metode_bayar')) {
                $table->string('metode_bayar', 30)->nullable();
            }
            if (!Schema::hasColumn('bcl_fin_jurnal', 'bukti')) {
                $table->string('bukti')->nullable();
            }
        });

        Schema::table('bcl_tr_renter', function (Blueprint $table) {
            if (!Schema::hasColumn('bcl_tr_renter', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('bcl_tr_renter', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable();
            }
        });

        if (!Schema::hasTable('bcl_room_inspections')) {
            Schema::create('bcl_room_inspections', function (Blueprint $table) {
                $table->id();
                $table->string('trans_id', 50)->index();
                $table->unsignedBigInteger('room_id')->index();
                $table->string('type', 10); // checkin | checkout
                $table->date('tanggal');
                $table->json('items')->nullable(); // [{inv_number, name, kondisi, catatan}]
                $table->unsignedSmallInteger('jumlah_kunci')->nullable();
                $table->string('meter_listrik', 50)->nullable();
                $table->text('catatan')->nullable();
                $table->string('foto')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bcl_audit_logs')) {
            Schema::create('bcl_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 60);
                $table->string('subject', 100)->nullable()->index();
                $table->text('description')->nullable();
                $table->json('data')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        $now = now();
        if (Schema::hasTable('permissions') && !DB::table('permissions')->where('name', 'bcl.supervisor')->where('guard_name', 'web')->exists()) {
            $permissionId = DB::table('permissions')->insertGetId(['name' => 'bcl.supervisor', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            $adminId = DB::table('roles')->where('name', 'Admin')->where('guard_name', 'web')->value('id');
            if ($adminId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $permissionId, 'role_id' => $adminId]);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bcl_audit_logs');
        Schema::dropIfExists('bcl_room_inspections');
        Schema::table('bcl_tr_renter', function (Blueprint $table) {
            foreach (['cancelled_at', 'cancelled_by'] as $column) {
                if (Schema::hasColumn('bcl_tr_renter', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        Schema::table('bcl_fin_jurnal', function (Blueprint $table) {
            foreach (['metode_bayar', 'bukti'] as $column) {
                if (Schema::hasColumn('bcl_fin_jurnal', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        $id = DB::table('permissions')->where('name', 'bcl.supervisor')->value('id');
        if ($id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
