<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewa_kamar', function (Blueprint $table) {
            $table->id('id_sewa');
            $table->foreignId('id_booking')->nullable()->unique()->constrained('booking', 'id_booking')->nullOnDelete();
            $table->foreignId('id_penghuni')->constrained('penghuni', 'id_penghuni')->restrictOnDelete();
            $table->foreignId('id_kamar')->constrained('kamar', 'id_kamar')->restrictOnDelete();
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->date('tenggat_pembayaran')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewa_kamar');
    }
};