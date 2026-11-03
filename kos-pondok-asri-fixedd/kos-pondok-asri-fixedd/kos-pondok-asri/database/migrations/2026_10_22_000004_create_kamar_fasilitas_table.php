<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kamar_fasilitas', function (Blueprint $table) {
            $table->foreignId('id_kamar')->constrained('kamar', 'id_kamar')->onDelete('cascade');
            $table->foreignId('id_fasilitas')->constrained('fasilitas', 'id_fasilitas')->onDelete('cascade');
            $table->primary(['id_kamar', 'id_fasilitas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kamar_fasilitas');
    }
};