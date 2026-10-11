<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mutasi Stok becomes the Penyesuaian Stok document:
 * - header: value of the document, approval (pending -> done / rejected) by someone other than the creator
 * - items: reason, chosen batch (needed to apply a pending document later), HPP and value at creation
 * Existing documents were posted immediately; their new columns stay empty / default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erm_mutasi_stok', function (Blueprint $t) {
            $t->decimal('total_nilai', 18, 2)->default(0)->after('status');
            $t->boolean('butuh_approval')->default(false)->after('total_nilai');
            $t->foreignId('approved_by')->nullable()->after('butuh_approval')->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable()->after('approved_by');
            $t->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $t->timestamp('rejected_at')->nullable()->after('rejected_by');
            $t->string('rejected_reason', 500)->nullable()->after('rejected_at');
        });

        Schema::table('erm_mutasi_stok_items', function (Blueprint $t) {
            $t->string('alasan', 50)->nullable()->after('jumlah');
            // batch_dipilih = a specific batch was chosen (batch may be NULL: the row without a batch name);
            // false = FEFO for keluar
            $t->boolean('batch_dipilih')->default(false)->after('alasan');
            $t->string('batch', 255)->nullable()->after('batch_dipilih');
            $t->boolean('batch_baru')->default(false)->after('batch');
            $t->date('expiration_date')->nullable()->after('batch_baru');
            $t->decimal('hpp', 18, 2)->default(0)->after('expiration_date');
            $t->decimal('nilai', 18, 2)->default(0)->after('hpp');
        });
    }

    public function down(): void
    {
        Schema::table('erm_mutasi_stok_items', function (Blueprint $t) {
            $t->dropColumn(['alasan', 'batch_dipilih', 'batch', 'batch_baru', 'expiration_date', 'hpp', 'nilai']);
        });

        Schema::table('erm_mutasi_stok', function (Blueprint $t) {
            $t->dropForeign(['approved_by']);
            $t->dropForeign(['rejected_by']);
            $t->dropColumn(['total_nilai', 'butuh_approval', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejected_reason']);
        });
    }
};
