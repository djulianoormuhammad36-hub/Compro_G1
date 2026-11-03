import { useEffect, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Banknote, Check, Clock, Copy, Printer, QrCode, Landmark, X } from 'lucide-react';
import PublicLayout from '../../components/public/PublicLayout';
import { Breadcrumb, Countdown, Steps } from '../../components/public/Bagian';
import { cx, rupiah } from '../../lib/utils';

const OPSI = [
    { nilai: 'Transfer Bank', ket: 'Transfer ke rekening pengelola, lalu kirim foto bukti transfer.', ikon: Landmark },
    { nilai: 'QRIS', ket: 'Scan kode QRIS pengelola, lalu kirim foto bukti pembayaran.', ikon: QrCode },
    { nilai: 'Bayar Langsung', ket: 'Datang langsung ke kos dan bayar ke pengelola dalam 1x24 jam.', ikon: Banknote },
];

function Banner({ judul, deskripsi, gagal, menunggu }) {
    const warna = gagal ? 'bg-rose-50' : menunggu ? 'bg-amber-50' : 'bg-ruma-tint';
    const lingkar = gagal ? 'bg-rose-600' : menunggu ? 'bg-amber-500' : 'bg-ruma-green';
    const Ikon = gagal ? X : menunggu ? Clock : Check;

    return (
        <div className={cx('mt-6 flex items-center gap-5 rounded-2xl p-6', warna)}>
            <span className={cx('flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-white', lingkar)}><Ikon size={22} /></span>
            <div>
                <h2 className="text-lg font-extrabold text-ruma-ink">{judul}</h2>
                <p className="mt-1 text-xs text-ruma-body">{deskripsi}</p>
            </div>
        </div>
    );
}

function SalinKode({ kode }) {
    const [tersalin, setTersalin] = useState(false);

    const salin = async () => {
        try {
            await navigator.clipboard.writeText(kode);
            setTersalin(true);
            setTimeout(() => setTersalin(false), 2000);
        } catch {
            window.prompt('Salin kode booking:', kode);
        }
    };

    return (
        <button type="button" onClick={salin} className="btn-outline !px-5 !py-2.5 !text-xs">
            <Copy size={13} /> {tersalin ? 'Tersalin ✓' : 'Salin Kode'}
        </button>
    );
}

export default function Payment({ booking, pembayaran, bayar }) {
    const { kamar } = booking;
    const sudahBayar = Boolean(pembayaran);
    const hangus = booking.status === 'Hangus' || pembayaran?.status === 'Hangus';
    const terverifikasi = pembayaran?.status === 'Terkonfirmasi';
    const ditolak = pembayaran?.status === 'Ditolak';
    const langsung = pembayaran?.metode === 'Bayar Langsung';
    const bisaBayar = !sudahBayar && booking.status === 'Menunggu' && kamar.status !== 'terisi';

    const form = useForm({ metode_pembayaran: 'Transfer Bank', bukti_bayar: null });
    const metode = sudahBayar ? pembayaran.metode : form.data.metode_pembayaran;
    const perluBukti = metode === 'Transfer Bank' || metode === 'QRIS';

    const pratinjau = useMemo(() => (form.data.bukti_bayar ? URL.createObjectURL(form.data.bukti_bayar) : null), [form.data.bukti_bayar]);
    useEffect(() => () => pratinjau && URL.revokeObjectURL(pratinjau), [pratinjau]);

    const kirim = (e) => {
        e.preventDefault();
        form.post(booking.urls.pay, { forceFormData: true, preserveScroll: true });
    };

    let banner;
    if (hangus) banner = { judul: 'Booking hangus', deskripsi: 'Waktu 1x24 jam telah habis sehingga booking dibatalkan otomatis dan kamar dibuka kembali. Silakan booking ulang.', gagal: true };
    else if (terverifikasi) banner = { judul: 'Pembayaran terverifikasi', deskripsi: 'Pembayaran sudah diterima dan dicatat pengelola. Bukti resmi berstatus LUNAS dapat dicetak.' };
    else if (ditolak) banner = { judul: 'Pembayaran ditolak', deskripsi: `Pengelola menolak pembayaran ini${pembayaran.alasan ? `: ${pembayaran.alasan}` : '.'} Silakan hubungi pengelola.`, gagal: true };
    else if (sudahBayar && langsung) banner = { judul: 'Menunggu kedatanganmu di kos', deskripsi: `Datang ke kos dan bayar langsung ke pengelola sebelum ${booking.batas_label ?? '24 jam dari sekarang'}. Lewat dari itu, booking hangus otomatis.`, menunggu: true };
    else if (sudahBayar) banner = { judul: 'Bukti pembayaran terkirim', deskripsi: 'Pengelola akan memeriksa foto buktimu. Bukti resmi berstatus LUNAS setelah terkonfirmasi.', menunggu: true };
    else banner = { judul: 'Booking berhasil disimpan', deskripsi: `Kamar ${kamar.nomor} ditahan untukmu. Pilih metode pembayaran di bawah.` };

    const tampilMetode = !hangus;

    return (
        <div className="mx-auto max-w-6xl px-5 py-10">
            <Head title="Pembayaran" />
            <Breadcrumb items={[['Beranda', '/'], ['Booking'], ['Pembayaran']]} />
            <Steps current={3} />

            <Banner {...banner} />

            {!sudahBayar && !hangus && booking.batas_waktu && (
                <p className="mt-4 flex items-center gap-2 text-xs font-semibold text-amber-700">
                    <Clock size={14} /> Sisa waktu tahan kamar: <Countdown iso={booking.batas_waktu} className="font-bold" />
                </p>
            )}
            {sudahBayar && langsung && !hangus && booking.batas_waktu && pembayaran.status === 'Menunggu Konfirmasi' && (
                <p className="mt-4 flex items-center gap-2 text-xs font-semibold text-amber-700">
                    <Clock size={14} /> Sisa waktu untuk datang: <Countdown iso={booking.batas_waktu} className="font-bold" />
                </p>
            )}

            <h1 className="mt-10 text-3xl font-extrabold tracking-tight text-ruma-ink md:text-4xl">Metode Pembayaran</h1>
            <p className="mt-2 text-sm text-ruma-mute">Pilih metode yang paling nyaman untukmu.</p>

            <div className="card mt-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-[9px] font-semibold uppercase tracking-wider text-ruma-mute">Referensi booking</p>
                    <p className="mt-1.5 text-base font-bold tracking-wide text-ruma-ink">{booking.kode}</p>
                </div>
                <SalinKode kode={booking.kode} />
            </div>

            <form onSubmit={kirim} className="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]">
                {tampilMetode && (
                    <div>
                        <h2 className="text-sm font-bold text-ruma-ink">Pilihan yang tersedia</h2>
                        <div className="mt-4 space-y-3">
                            {OPSI.map(({ nilai, ket, ikon: Ikon }) => {
                                const dipilih = metode === nilai;
                                return (
                                    <label key={nilai} className={cx('block', bisaBayar ? 'cursor-pointer' : 'cursor-default')}>
                                        <input type="radio" name="metode_pembayaran" value={nilai} checked={dipilih} disabled={!bisaBayar}
                                            onChange={() => form.setData('metode_pembayaran', nilai)} className="sr-only" />
                                        <div className={cx('card flex items-center gap-4 p-4 transition', dipilih && 'border-ruma-green ring-1 ring-ruma-green', !dipilih && sudahBayar && 'opacity-50')}>
                                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ruma-tint text-ruma-green"><Ikon size={18} /></span>
                                            <div className="flex-1">
                                                <p className="text-[13px] font-bold text-ruma-ink">{nilai}</p>
                                                <p className="mt-0.5 text-[11px] text-ruma-mute">{ket}</p>
                                            </div>
                                            <span className={cx('flex h-5 w-5 items-center justify-center rounded-full border', dipilih ? 'border-ruma-green bg-ruma-green' : 'border-ruma-line')}>
                                                {dipilih && <Check size={10} className="text-white" />}
                                            </span>
                                        </div>
                                    </label>
                                );
                            })}
                        </div>

                        {/* Petunjuk sesuai metode */}
                        {metode === 'Transfer Bank' && (
                            <div className="card mt-5 p-5">
                                <p className="text-xs font-bold text-ruma-ink">Rekening tujuan transfer</p>
                                {bayar.info_transfer
                                    ? <p className="mt-2 whitespace-pre-line rounded-xl bg-ruma-bg p-4 text-sm font-semibold text-ruma-ink">{bayar.info_transfer}</p>
                                    : <p className="mt-2 text-xs text-ruma-mute">Informasi rekening belum diisi pengelola. Silakan hubungi pemilik kos.</p>}
                                <p className="mt-3 text-[11px] text-ruma-mute">Nominal: <span className="font-bold text-ruma-green">{rupiah(kamar.biaya)}</span></p>
                            </div>
                        )}
                        {metode === 'QRIS' && (
                            <div className="card mt-5 p-5 text-center">
                                <p className="text-xs font-bold text-ruma-ink">Scan QRIS untuk membayar {rupiah(kamar.biaya)}</p>
                                {bayar.qris
                                    ? <img src={bayar.qris} alt="Kode QRIS pengelola" className="mx-auto mt-4 max-h-80 rounded-xl border border-ruma-line" />
                                    : <p className="mt-2 text-xs text-ruma-mute">Gambar QRIS belum diisi pengelola. Silakan hubungi pemilik kos.</p>}
                            </div>
                        )}
                        {metode === 'Bayar Langsung' && (
                            <div className="card mt-5 p-5">
                                <p className="text-xs font-bold text-ruma-ink">Bayar langsung di kos</p>
                                <ul className="mt-2 list-inside list-disc space-y-1 text-xs leading-relaxed text-ruma-body">
                                    <li>Datang ke kos dan bayar <b>{rupiah(kamar.biaya)}</b> ke pengelola dalam <b>{bayar.jam_tahan} jam</b> sejak memilih metode ini.</li>
                                    <li>Jika lewat dari batas waktu, booking <b>hangus otomatis</b> dan kamar dibuka untuk orang lain.</li>
                                    {bayar.lokasi && <li>Alamat: {bayar.lokasi}</li>}
                                    {bayar.kontak && <li>Kontak pengelola: {bayar.kontak}</li>}
                                </ul>
                            </div>
                        )}

                        {/* Unggah foto bukti (transfer & QRIS) */}
                        {bisaBayar && perluBukti && (
                            <div className="mt-5">
                                <label className="label" htmlFor="bukti_bayar">Foto bukti pembayaran <span className="font-normal text-ruma-mute">(wajib, JPG/PNG maks. 5MB)</span></label>
                                <input id="bukti_bayar" type="file" accept="image/png,image/jpeg"
                                    onChange={(e) => form.setData('bukti_bayar', e.target.files?.[0] ?? null)}
                                    className="block w-full rounded-xl border border-ruma-line bg-white text-xs text-ruma-body file:mr-3 file:border-0 file:bg-ruma-tint file:px-4 file:py-3 file:text-xs file:font-semibold file:text-ruma-green" />
                                {pratinjau && <img src={pratinjau} alt="Pratinjau bukti" className="mt-3 max-h-48 rounded-xl border border-ruma-line" />}
                                {form.errors.bukti_bayar && <p className="mt-1 text-xs text-rose-600">{form.errors.bukti_bayar}</p>}
                            </div>
                        )}
                        {form.errors.metode_pembayaran && <p className="mt-2 text-xs text-rose-600">{form.errors.metode_pembayaran}</p>}
                    </div>
                )}

                <aside className={cx('card h-fit p-6', !tampilMetode && 'lg:col-start-2')}>
                    <h3 className="text-sm font-bold text-ruma-ink">Ringkasan pembayaran</h3>
                    <dl className="mt-4 space-y-3 border-t border-ruma-line pt-4 text-xs">
                        <div className="flex justify-between gap-4"><dt className="text-ruma-mute">Kamar</dt><dd className="font-semibold text-ruma-ink">Kamar {kamar.nomor}</dd></div>
                        <div className="flex justify-between gap-4"><dt className="text-ruma-mute">Periode</dt><dd className="text-right font-semibold text-ruma-ink">{booking.periode}</dd></div>
                        {sudahBayar && <div className="flex justify-between gap-4"><dt className="text-ruma-mute">Metode</dt><dd className="font-semibold text-ruma-ink">{pembayaran.metode}</dd></div>}
                    </dl>
                    <div className="mt-5 border-t border-ruma-line pt-4">
                        <p className="text-xs font-semibold text-ruma-ink">Total sewa / bulan</p>
                        <p className="mt-2 text-2xl font-extrabold text-ruma-green">{rupiah(kamar.biaya)}</p>
                    </div>
                </aside>
            </form>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                <Link href={booking.urls.review} className="btn-outline">Kembali ke Informasi Booking</Link>

                {bisaBayar && (
                    <button type="button" onClick={kirim} disabled={form.processing || (perluBukti && !form.data.bukti_bayar)} className="btn-primary">
                        {form.processing ? 'Mengirim...' : metode === 'Bayar Langsung' ? 'Konfirmasi Bayar Langsung' : 'Kirim Bukti Pembayaran'}
                    </button>
                )}
                {hangus && <Link href="/kamar?status=kosong" className="btn-primary">Cari Kamar Lain</Link>}

                {sudahBayar && !hangus ? (
                    <a href={pembayaran.urls.cetak} target="_blank" rel="noopener noreferrer" className="btn-primary"><Printer size={14} /> Cetak Bukti Pembayaran</a>
                ) : !hangus && (
                    <span className="btn-outline cursor-not-allowed opacity-60" title="Tersedia setelah pembayaran dikirim"><Printer size={14} /> Cetak Bukti Pembayaran</span>
                )}
                {sudahBayar && <Link href={pembayaran.urls.bukti} className="text-xs font-semibold text-ruma-green hover:underline">Lihat bukti</Link>}
            </div>
            <p className="mt-4 text-[10px] text-ruma-mute">
                {!sudahBayar && !hangus && 'Tombol cetak aktif setelah pembayaran dikirim. '}
                Bukti berstatus LUNAS setelah pembayaran terkonfirmasi pengelola.
            </p>
        </div>
    );
}

Payment.layout = (page) => <PublicLayout>{page}</PublicLayout>;
