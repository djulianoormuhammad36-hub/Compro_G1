import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, DoorClosed, Trash2 } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Konfirmasi, Lencana, warnaPembayaran } from '../../../components/admin/Bagian';
import { rupiah } from '../../../lib/utils';

export default function Show({ penghuni, riwayat, bisaHapus }) {
    const [dialog, setDialog] = useState(null); // 'keluar' | 'hapus'

    return (
        <div className="space-y-6">
            <Head title={`Penghuni ${penghuni.nama}`} />
            <Link href="/admin/penghuni" className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600"><ArrowLeft size={15} /> Kembali ke daftar penghuni</Link>

            <div className="grid gap-6 lg:grid-cols-3">
                <section className="a-card p-6 lg:col-span-2">
                    <div className="flex items-start justify-between">
                        <div>
                            <h2 className="text-xl font-extrabold text-slate-900">{penghuni.nama}</h2>
                            <p className="text-xs text-slate-400">Terdaftar {penghuni.terdaftar}</p>
                        </div>
                        <Lencana warna={penghuni.aktif ? 'hijau' : 'abu'}>{penghuni.aktif ? 'Sedang ngekos' : 'Tidak aktif'}</Lencana>
                    </div>

                    <dl className="mt-6 grid gap-5 sm:grid-cols-2">
                        <div className="rounded-xl bg-slate-50 p-4"><dt className="text-xs text-slate-400">Nomor KTP</dt><dd className="mt-1 font-mono text-base font-bold tracking-wider text-slate-900">{penghuni.no_ktp}</dd></div>
                        <div className="rounded-xl bg-slate-50 p-4"><dt className="text-xs text-slate-400">Kontak (HP / WhatsApp)</dt><dd className="mt-1 text-base font-bold text-slate-900">{penghuni.kontak}</dd></div>
                    </dl>
                    <p className="mt-4 text-[11px] text-slate-400">Identitas ini bersifat rahasia dan hanya terlihat oleh admin.</p>
                </section>

                <section className="a-card p-6">
                    <h3 className="text-base font-bold text-slate-900">Tindakan</h3>
                    <div className="mt-4 space-y-3">
                        {penghuni.aktif && (
                            <button onClick={() => setDialog('keluar')} className="a-btn-outline w-full"><DoorClosed size={16} /> Tandai Sudah Keluar</button>
                        )}
                        <button onClick={() => setDialog('hapus')} disabled={!bisaHapus} className="a-btn-danger w-full"><Trash2 size={16} /> Hapus Data Penghuni</button>
                        {!bisaHapus && <p className="text-xs text-slate-400">Penghuni masih ngekos. Tandai keluar dulu sebelum menghapus datanya.</p>}
                    </div>
                </section>
            </div>

            <section className="a-card overflow-hidden">
                <h3 className="border-b border-slate-100 px-6 py-4 text-base font-bold text-slate-900">Riwayat Sewa & Pembayaran</h3>
                {riwayat.length > 0 ? riwayat.map((r) => (
                    <div key={r.id} className="border-b border-slate-100 px-6 py-4 last:border-0">
                        <p className="text-sm font-bold text-slate-800">Kamar {r.kamar} <span className="font-normal text-slate-400">· masuk {r.masuk}{r.keluar ? ` · keluar ${r.keluar}` : ' · masih berjalan'}</span></p>
                        <ul className="mt-2 space-y-1.5">
                            {r.pembayaran.map((b) => (
                                <li key={b.id} className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                                    <span>{b.tanggal} · {b.metode} · <b>{rupiah(b.jumlah)}</b></span>
                                    <span className="flex items-center gap-3"><Lencana warna={warnaPembayaran(b.status)}>{b.status}</Lencana><Link href={`/admin/konfirmasi/${b.id}`} className="font-semibold text-emerald-600 hover:underline">Detail</Link></span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )) : <p className="px-6 py-10 text-center text-sm text-slate-400">Belum ada riwayat sewa.</p>}
            </section>

            <Konfirmasi buka={dialog === 'keluar'} onTutup={() => setDialog(null)} judul="Tandai penghuni sudah keluar?" tombol="Ya, sudah keluar"
                pesan={`${penghuni.nama} dianggap berhenti ngekos hari ini. Kamar yang ditempati otomatis kembali berstatus tersedia.`}
                onYa={() => router.post(`/admin/penghuni/${penghuni.id}/keluar`, {}, { preserveScroll: true, onFinish: () => setDialog(null) })} />
            <Konfirmasi buka={dialog === 'hapus'} onTutup={() => setDialog(null)} bahaya judul="Hapus data penghuni?" tombol="Hapus permanen"
                pesan="Nama, nomor KTP, dan kontak penghuni dihapus permanen. Catatan pembayaran tetap tersimpan (tanpa identitas) untuk laporan keuangan."
                onYa={() => router.delete(`/admin/penghuni/${penghuni.id}`, { onFinish: () => setDialog(null) })} />
        </div>
    );
}

Show.layout = (page) => <AdminLayout title="Detail Penghuni">{page}</AdminLayout>;
