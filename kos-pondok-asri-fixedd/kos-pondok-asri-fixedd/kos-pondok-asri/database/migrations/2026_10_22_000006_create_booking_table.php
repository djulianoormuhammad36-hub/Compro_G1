<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id('id_booking');
            $table->uuid('token')->unique();
            $table->foreignId('id_kamar')->constrained('kamar', 'id_kamar')->restrictOnDelete();
            $table->string('nama_pendaftar');
            $table->text('no_ktp'); // terenkripsi
            $table->string('kontak', 20);
            $table->date('tanggal_booking');
            $table->string('status_booking')->default('Menunggu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};