<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('notulensi_rapat_todos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notulensi_rapat_id');
            $table->string('task');
            $table->string('status')->default('pending'); // pending, done, cancelled, etc.
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->foreign('notulensi_rapat_id')->references('id')->on('notulensi_rapat')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notulensi_rapat_todos');
    }
};
