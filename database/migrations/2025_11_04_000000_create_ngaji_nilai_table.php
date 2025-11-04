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
        Schema::create('ngaji_nilai', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('test')->nullable();
            $table->date('date')->nullable();

            // Score fields (use decimal to allow fractional scores)
            $table->decimal('nilai_makhroj', 5, 2)->nullable();
            $table->decimal('nilai_tajwid', 5, 2)->nullable();
            $table->decimal('nilai_panjang_pendek', 5, 2)->nullable();
            $table->decimal('nilai_kelancaran', 5, 2)->nullable();

            $table->decimal('total_nilai', 6, 2)->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();

            // If your employees table is 'hrd_employee' and uses id as PK, add FK
            if (Schema::hasTable('hrd_employee')) {
                $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ngaji_nilai', function (Blueprint $table) {
            // drop foreign if exists
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            // best-effort: attempt to drop foreign key named conventionally
            try {
                $table->dropForeign(['employee_id']);
            } catch (\Exception $e) {
                // ignore if FK doesn't exist
            }
        });

        Schema::dropIfExists('ngaji_nilai');
    }
};
