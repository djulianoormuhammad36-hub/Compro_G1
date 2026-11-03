<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penghuni', function (Blueprint $table) {
            $table->id('id_penghuni');
            $table->string('nama');
            // terenkripsi (cast 'encrypted'); pencarian memakai no_ktp_hash
            $table->text('no_ktp');
            $table->string('no_ktp_hash', 64)->index();
            $table->string('kontak', 20);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penghuni');
    }
};