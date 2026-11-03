<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kos_info', function (Blueprint $table) {
            $table->text('deskripsi_kos')->nullable()->after('nama_kos');
            $table->text('info_pembayaran')->nullable()->after('lokasi'); // rekening bank dll (ditempel admin)
            $table->string('foto_qris')->nullable()->after('foto_kos');   // gambar QRIS (ditempel admin)
        });

        Schema::table('kamar', function (Blueprint $table) {
            $table->string('foto_kamar')->nullable()->after('detail_kamar');
        });

        Schema::table('booking', function (Blueprint $table) {
            // Batas waktu tahan kamar; lewat batas -> booking hangus otomatis.
            $table->dateTime('batas_waktu')->nullable()->after('status_booking');
        });

        // Data penghuni boleh dihapus (identitas hilang) tanpa menghapus riwayat pembayaran.
        Schema::table('sewa_kamar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_penghuni')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking', fn (Blueprint $t) => $t->dropColumn('batas_waktu'));
        Schema::table('kamar', fn (Blueprint $t) => $t->dropColumn('foto_kamar'));
        Schema::table('kos_info', fn (Blueprint $t) => $t->dropColumn(['deskripsi_kos', 'info_pembayaran', 'foto_qris']));
    }
};
