import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, Image as GambarIkon, MessageCircle, Search } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Konfirmasi, Lencana, Modal, Paginasi, warnaPembayaran } from '../../../components/admin/Bagian';
import { cx, rupiah } from '../../../lib/utils';

const TAB = [
    ['Menunggu Konfirmasi', 'Menunggu'],
    ['Terkonfirmasi', 'Terkonfirmasi'],
    ['Ditolak', 'Ditolak'],
    ['Hangus', 'Hangus'],
    ['all', 'Semua'],
];

function FormTolak({ item, onSelesai }) {
    const form = useForm({ alasan_penolakan: '' });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post(`/admin/konfirmasi/${item.id}/reject`, { preserveScroll: true, onSuccess: onSelesai }); }} className="space-y-4">
            <p className="text-sm text-slate-500">Atas nama <b className="text-slate-800">{item.nama}</b> · Kamar {item.kamar}. Kamar akan dilepas kembali.</p>
            <div>
                <textarea rows={3} required maxLength={255} value={form.data.alasan_penolakan} onChange={(e) => form.setData('alasan_penolakan', e.target.value)} placeholder="Alasan penolakan (mis. nominal tidak sesuai)" className="a-field" />
                {form.errors.alasan_penolakan && <p className="mt-1 text-xs text-rose-600">{form.errors.alasan_penolakan}</p>}
            </div>
            <div className="flex justify-end gap-3">
                <button type="button" onClick={onSelesai} className="a-btn-outline">Batal</button>
                <button type="submit" disabled={form.processing} className="a-btn bg-rose-600 text-white hover:bg-rose-700">Tolak Pembayaran</button>
            </div>
        </form>
    );
}

export default function Index({ pembayarans, filters }) {
    const [search, setSearch] = useState(filters.search);
    const [tolak, setTolak] = useState(null);
    const [setuju, setSetuju] = useState(null);
    const awal = useRef(true);

    const terapkan = (ubah) => {
        const d = { search, status: filters.status, ...ubah };
        router.get('/admin/konfirmasi', { ...(d.search ? { search: d.search } : {}), status: d.status }, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (awal.current) { awal.current = false; return undefined; }
        const t = setTimeout(() => terapkan({ search }), 400);
        return () => clearTimeout(t);
    }, [search]);

    return (
        <div className="space-y-6">
            <Head title="Konfirmasi Pembayaran" />

            <div className="a-card space-y-4 p-4">
                <div className="flex flex-wrap gap-2">
                    {TAB.map(([nilai, label]) => (
                        <button key={nilai} onClick={() => terapkan({ status: nilai })}
                            className={cx('rounded-full px-4 py-2 text-xs font-semibold transition', filters.status === nilai ? 'bg-emerald-600 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200')}>
                            {label}
                        </button>
                    ))}
                </div>
                <div className="relative">
                    <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama atau nomor kamar..." className="a-field !pl-10" />
                </div>
            </div>

            <div className="a-card overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-100/70 text-xs font-bold uppercase tracking-wider text-slate-600">
                                <th className="px-6 py-4">Penyewa</th>
                                <th className="px-6 py-4">Kamar</th>
                                <th className="px-6 py-4">Metode</th>
                                <th className="px-6 py-4">Jumlah</th>
                                <th className="px-6 py-4">Tanggal</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-sm text-slate-700">
                            {pembayarans.data.length > 0 ? pembayarans.data.map((p) => (
                                <tr key={p.id} className="hover:bg-slate-50/80">
                                    <td className="px-6 py-4 font-semibold text-slate-900">{p.nama}<span className="block text-xs font-normal text-slate-400">{p.kontak}</span></td>
                                    <td className="px-6 py-4">Kamar {p.kamar}</td>
                                    <td className="px-6 py-4 text-xs">{p.metode}{p.ada_bukti && <span className="mt-1 flex items-center gap-1 font-semibold text-emerald-600"><GambarIkon size={12} /> ada foto bukti</span>}</td>
                                    <td className="px-6 py-4 font-semibold text-emerald-600">{rupiah(p.jumlah)}</td>
                                    <td className="px-6 py-4 text-xs">{p.tanggal}</td>
                                    <td className="px-6 py-4"><Lencana warna={warnaPembayaran(p.status)}>{p.status === 'Menunggu Konfirmasi' ? 'Menunggu' : p.status}</Lencana></td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center justify-center gap-2">
                                            <Link href={`/admin/konfirmasi/${p.id}`} className="rounded-lg p-2 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600" title="Detail"><Eye size={16} /></Link>
                                            {p.status === 'Menunggu Konfirmasi' && (
                                                <>
                                                    <button onClick={() => setSetuju(p)} className="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Setujui</button>
                                                    <button onClick={() => setTolak(p)} className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100">Tolak</button>
                                                </>
                                            )}

                                                                                        {p.wa && (
                                                <a href={p.wa.url} target="_blank" rel="noopener noreferrer" className="rounded-lg p-2 text-emerald-600 hover:bg-emerald-50" title="Kabari penyewa via WhatsApp"><MessageCircle size={16} /></a>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            )) : <tr><td colSpan={7} className="py-14 text-center text-slate-400">Tidak ada transaksi pada filter ini.</td></tr>}
                        </tbody>
                    </table>
                </div>
                <Paginasi links={pembayarans.links} />
            </div>

            <Modal buka={tolak !== null} onTutup={() => setTolak(null)} judul="Tolak Pembayaran" lebar="max-w-md">
                {tolak && <FormTolak key={tolak.id} item={tolak} onSelesai={() => setTolak(null)} />}
            </Modal>

            <Konfirmasi buka={setuju !== null} onTutup={() => setSetuju(null)} judul="Setujui pembayaran?" tombol="Ya, setujui"
                pesan={`Pembayaran ${setuju?.nama ?? ''} (${setuju?.metode ?? ''}) untuk Kamar ${setuju?.kamar ?? ''} akan dikonfirmasi. Kamar berstatus terisi dan penghuni menjadi aktif.`}
                onYa={() => router.post(`/admin/konfirmasi/${setuju.id}/approve`, {}, { onFinish: () => setSetuju(null) })} />
        </div>
    );
}

Index.layout = (page) => <AdminLayout title="Konfirmasi Pembayaran">{page}</AdminLayout>;
