import { Head, Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import PublicLayout from '../../components/public/PublicLayout';
import { StatusKamar } from '../../components/public/Foto';
import { Breadcrumb, Countdown, Steps } from '../../components/public/Bagian';
import { rupiah } from '../../lib/utils';

export default function Review({ booking }) {
    const { kamar } = booking;
    const menunggu = booking.status === 'Menunggu';
    const hangus = booking.status === 'Hangus';

    const baris = [
        ['Nama lengkap', booking.nama],
        ['Nomor KTP', booking.ktp_samaran],
        ['Nomor handphone', booking.kontak],
        ['Tanggal booking', booking.tanggal],
    ];

    return (
        <div className="mx-auto max-w-6xl px-5 py-10">
            <Head title="Review Booking" />
            <Breadcrumb items={[['Beranda', '/'], ['Booking'], ['Konfirmasi']]} />

            <h1 className="mt-6 text-3xl font-extrabold tracking-tight text-ruma-ink md:text-4xl">Periksa kembali booking kamu</h1>
            <p className="mt-2 text-sm text-ruma-mute">Pastikan informasi di bawah sudah benar sebelum melanjutkan ke pembayaran.</p>

            <Steps current={2} />

            {menunggu && booking.batas_waktu && (
                <div className="mt-6 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">
                    <Clock size={16} className="shrink-0" />
                    <p>Kamar {kamar.nomor} ditahan untukmu. Selesaikan pembayaran dalam <Countdown iso={booking.batas_waktu} className="font-bold" /> atau booking hangus otomatis.</p>
                </div>
            )}
            {hangus && (
                <p className="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700">Waktu booking sudah habis (hangus). Silakan booking ulang kamar yang tersedia.</p>
            )}

            <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]">
                <section className="card p-6 md:p-7">
                    <div className="flex items-center justify-between border-b border-ruma-line pb-4">
                        <h2 className="text-base font-bold text-ruma-ink">Ringkasan Booking</h2>
                        <StatusKamar status={kamar.status} />
                    </div>

                    <p className="mt-5 text-[10px] font-semibold uppercase tracking-wider text-ruma-mute">Kamar yang dipilih</p>
                    <p className="mt-2 text-2xl font-extrabold text-ruma-ink">Kamar {kamar.nomor}</p>
                    <p className="mt-1 text-sm font-bold text-ruma-green">{rupiah(kamar.biaya)} / bulan</p>

                    <dl className="mt-6 divide-y divide-ruma-line border-t border-ruma-line text-xs">
                        {baris.map(([label, nilai]) => (
                            <div key={label} className="grid grid-cols-[140px_1fr] items-center gap-4 py-3.5">
                                <dt className="text-ruma-mute">{label}</dt>
                                <dd className="font-semibold text-ruma-ink">{nilai}</dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <div className="space-y-6">
                    <aside className="rounded-2xl bg-ruma-tint p-6">
                        <h3 className="text-sm font-bold text-ruma-ink">Data sudah sesuai?</h3>
                        <p className="mt-3 text-[11px] leading-relaxed text-ruma-body">Kamu masih bisa kembali untuk memperbaiki data sebelum melanjutkan.</p>
                        <p className="mt-8 text-[10px] text-ruma-mute">Nomor KTP ditampilkan tersamarkan untuk menjaga privasi.</p>
                    </aside>
                    <aside className="card p-6">
                        <h3 className="text-sm font-bold text-ruma-ink">Langkah berikutnya</h3>
                        <p className="mt-3 text-[11px] leading-relaxed text-ruma-body">Pilih metode pembayaran: transfer bank, QRIS (kirim foto bukti), atau bayar langsung ke kos dalam 24 jam.</p>
                    </aside>
                </div>
            </div>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
                {menunggu ? (
                    <>
                        <Link href={booking.urls.ubah} className="btn-outline">Ubah Data</Link>
                        <Link href={booking.urls.payment} className="btn-primary">Lanjut ke Pembayaran</Link>
                    </>
                ) : (
                    <>
                        <Link href="/kamar" className="btn-outline">Lihat Kamar Lain</Link>
                        <Link href={booking.urls.payment} className="btn-primary">Lihat Pembayaran</Link>
                    </>
                )}
            </div>
        </div>
    );
}

Review.layout = (page) => <PublicLayout>{page}</PublicLayout>;
