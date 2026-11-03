import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, ImageOff, Maximize2, MessageCircle } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Konfirmasi, Lencana, Modal, warnaPembayaran } from '../../../components/admin/Bagian';
import { rupiah } from '../../../lib/utils';

export default function Show({ pembayaran: p }) {
    const [besar, setBesar] = useState(false);
    const [setuju, setSetuju] = useState(false);
    const tolak = useForm({ alasan_penolakan: '' });
    const menunggu = p.status === 'Menunggu Konfirmasi';
    const langsung = p.metode === 'Bayar Langsung';

    return (
        <div className="space-y-6">
            <Head title={`Pembayaran #${p.id}`} />
            <Link href="/admin/konfirmasi" className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600"><ArrowLeft size={15} /> Kembali ke daftar</Link>

            <div className="grid gap-6 lg:grid-cols-3">
                <section className="a-card p-6">
                    <h2 className="text-base font-bold text-slate-900">Bukti Pembayaran</h2>
                    <div className="mt-4">
                        {p.ada_bukti ? (
                            <button type="button" onClick={() => setBesar(true)} className="group relative block w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                <img src={p.bukti_url} alt="Foto bukti pembayaran" className="max-h-96 w-full object-contain" />
                                <span className="absolute right-3 top-3 flex items-center gap-1.5 rounded-lg bg-slate-900/70 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100"><Maximize2 size={12} /> Perbesar</span>
                            </button>
                        ) : (
                            <div className="flex h-56 flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm text-slate-400">
                                <ImageOff size={28} />
                                {langsung ? 'Pembayaran langsung di kos — tidak ada foto bukti.' : 'Foto bukti belum tersedia.'}
                            </div>
                        )}
                    </div>
                </section>

                <section className="a-card space-y-5 p-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-bold text-slate-900">Transaksi #{p.id}</h2>
                        <Lencana warna={warnaPembayaran(p.status)}>{p.status}</Lencana>
                    </div>
                    <dl className="space-y-3 text-sm">
                        {[['Jumlah', rupiah(p.jumlah)], ['Metode', p.metode], ['Tanggal', p.tanggal], ['Kamar', `Kamar ${p.kamar}`], ['Mulai sewa', p.masuk], ['Tenggat berikutnya', p.tenggat]].map(([l, v]) => (
                            <div key={l} className="flex justify-between gap-4"><dt className="text-slate-400">{l}</dt><dd className="text-right font-semibold text-slate-800">{v ?? '-'}</dd></div>
                        ))}
                    </dl>
                    {langsung && menunggu && p.batas && (
                        <p className="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-xs text-amber-800"><Clock size={14} className="mt-0.5 shrink-0" /> Penyewa harus datang sebelum {p.batas}. Lewat dari itu booking hangus otomatis.</p>
                    )}
                    {p.alasan && <p className="rounded-xl bg-rose-50 p-3 text-xs text-rose-700"><b>Alasan penolakan:</b> {p.alasan}</p>}
                </section>

                <section className="a-card space-y-5 p-6">
                    <h2 className="text-base font-bold text-slate-900">Identitas Penyewa</h2>
                    {p.penghuni ? (
                        <dl className="space-y-3 text-sm">
                            <div><dt className="text-xs text-slate-400">Nama lengkap</dt><dd className="font-semibold text-slate-800">{p.penghuni.nama}</dd></div>
                            <div><dt className="text-xs text-slate-400">Nomor KTP</dt><dd className="font-mono font-semibold tracking-wider text-slate-800">{p.penghuni.no_ktp}</dd></div>
                            <div><dt className="text-xs text-slate-400">Kontak</dt><dd className="font-semibold text-slate-800">{p.penghuni.kontak}</dd></div>
                            <Link href={`/admin/penghuni/${p.penghuni.id}`} className="inline-block text-xs font-semibold text-emerald-600 hover:underline">Lihat profil penghuni →</Link>
                        </dl>
                    ) : <p className="text-sm text-slate-400">Data penghuni sudah dihapus.</p>}

                                        {p.wa && (
                        <div className="space-y-2 border-t border-slate-100 pt-5">
                            <a href={p.wa.url} target="_blank" rel="noopener noreferrer" className="a-btn-primary w-full">
                                <MessageCircle size={16} /> Kabari penyewa via WhatsApp
                            </a>
                            <details className="text-xs text-slate-500">
                                <summary className="cursor-pointer font-semibold">Lihat isi pesan</summary>
                                <pre className="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-3 font-sans text-slate-600">{p.wa_pesan}</pre>
                            </details>
                        </div>
                    )}
                    
                    {menunggu && (
                        <div className="space-y-3 border-t border-slate-100 pt-5">
                            <button onClick={() => setSetuju(true)} className="a-btn-primary w-full">{langsung ? 'Terima Pembayaran (sudah bayar di kos)' : 'Setujui Pembayaran'}</button>
                            <form onSubmit={(e) => { e.preventDefault(); tolak.post(`/admin/konfirmasi/${p.id}/reject`, { preserveScroll: true }); }} className="space-y-2">
                                <textarea rows={2} required maxLength={255} value={tolak.data.alasan_penolakan} onChange={(e) => tolak.setData('alasan_penolakan', e.target.value)} placeholder="Alasan penolakan..." className="a-field" />
                                {tolak.errors.alasan_penolakan && <p className="text-xs text-rose-600">{tolak.errors.alasan_penolakan}</p>}
                                <button type="submit" disabled={tolak.processing} className="a-btn-danger w-full">Tolak Pembayaran</button>
                            </form>
                        </div>
                    )}
                </section>
            </div>

            <Modal buka={besar} onTutup={() => setBesar(false)} judul="Foto bukti pembayaran" lebar="max-w-4xl">
                <img src={p.bukti_url} alt="Foto bukti pembayaran" className="mx-auto max-h-[75vh] rounded-lg" />
            </Modal>

            <Konfirmasi buka={setuju} onTutup={() => setSetuju(false)} judul="Setujui pembayaran?" tombol="Ya, setujui"
                pesan="Kamar akan berstatus terisi dan penghuni menjadi aktif."
                onYa={() => router.post(`/admin/konfirmasi/${p.id}/approve`, {}, { onFinish: () => setSetuju(false) })} />
        </div>
    );
}

Show.layout = (page) => <AdminLayout title="Detail Pembayaran">{page}</AdminLayout>;
