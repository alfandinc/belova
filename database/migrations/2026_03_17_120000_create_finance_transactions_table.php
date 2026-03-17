<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal')->index();
            $table->string('visitation_id')->nullable()->index();
            $table->foreignId('invoice_id')->nullable()->constrained('finance_invoices')->nullOnDelete();
            $table->decimal('jumlah', 15, 2);
            $table->enum('jenis_transaksi', ['in', 'out'])->default('in')->index();
            $table->string('metode_bayar')->nullable()->index();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};