<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom letterheads (kop) of the PDFs. One row per klinik (lingkup 'klinik'), one for the company
 * ('perusahaan': pengajuan dana, slip gaji dokter, memo without klinik) and one for the lab ('lab').
 * An empty image column means the built-in artwork in public/img is used (App\Support\PdfLetterhead).
 * Each image keeps the uploaded original (*_asli) and the small JPEG the PDFs read (made once at upload).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pdf_kop')) {
            return;
        }
        Schema::create('pdf_kop', function (Blueprint $table) {
            $table->id();
            $table->string('lingkup', 20);                    // klinik | perusahaan | lab
            $table->unsignedBigInteger('klinik_id')->nullable(); // only for lingkup klinik
            foreach (['header', 'footer', 'logo', 'ikon'] as $gambar) {
                $table->string($gambar)->nullable();          // processed JPEG on the public disk
                $table->string($gambar . '_asli')->nullable(); // uploaded original
            }
            $table->string('nama')->nullable();               // perusahaan: name in the text letterhead
            $table->text('alamat')->nullable();               // perusahaan: address lines (one per line)
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['lingkup', 'klinik_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pdf_kop');
    }
};
