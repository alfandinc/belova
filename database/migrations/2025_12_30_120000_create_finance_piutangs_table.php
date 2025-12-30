<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancePiutangsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finance_piutangs', function (Blueprint $table) {
            $table->id();
            // Visitation id is a string in this app
            $table->string('visitation_id')->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_status')->default('unpaid')->index(); // e.g. unpaid, partial, paid
            $table->timestamp('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();

            // Foreign keys (best-effort; nullable where appropriate)
            $table->foreign('invoice_id')->references('id')->on('finance_invoices')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finance_piutangs');
    }
}
