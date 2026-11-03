<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');
            $table->uuid('token')->unique();
            $table->foreignId('id_sewa')->constrained('sewa_kamar', 'id_sewa')->restrictOnDelete();
            $table->dateTime('tanggal_bayar');
            $table->integer('jumlah_bayar');
            $table->string('metode_pembayaran');
            $table->string('bukti_bayar')->nullable();
            $table->string('status_pembayaran')->default('Menunggu Konfirmasi');
            $table->string('alasan_penolakan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};