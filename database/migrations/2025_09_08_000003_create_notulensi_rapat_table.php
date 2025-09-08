<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('notulensi_rapat', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->longText('notulen');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notulensi_rapat');
    }
};
