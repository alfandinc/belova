<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hrd_pengajuan_ganti_shift', function (Blueprint $table) {
            $table->boolean('is_tukar_shift')->default(false)->after('alasan');
            $table->unsignedBigInteger('target_employee_id')->nullable()->after('is_tukar_shift');
            $table->enum('target_employee_approval_status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu')->after('target_employee_id');
            $table->timestamp('target_employee_approval_date')->nullable()->after('target_employee_approval_status');
            $table->text('target_employee_notes')->nullable()->after('target_employee_approval_date');
            
            $table->foreign('target_employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hrd_pengajuan_ganti_shift', function (Blueprint $table) {
            $table->dropForeign(['target_employee_id']);
            $table->dropColumn([
                'is_tukar_shift',
                'target_employee_id',
                'target_employee_approval_status',
                'target_employee_approval_date',
                'target_employee_notes'
            ]);
        });
    }
};
