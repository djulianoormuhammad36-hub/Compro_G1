<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kos_info', function (Blueprint $table) {
            $table->id('id_kos');
            $table->string('nama_kos');
            $table->text('peraturan_kos');
            $table->string('kontak_pemilik');
            $table->string('lokasi');
            $table->string('foto_kos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kos_info');
    }
};