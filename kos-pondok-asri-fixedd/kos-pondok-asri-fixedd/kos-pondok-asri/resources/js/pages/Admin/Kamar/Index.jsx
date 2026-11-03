import { useEffect, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Konfirmasi, Kolom, Modal, Paginasi, UnggahFoto } from '../../../components/admin/Bagian';
import { FOTO_BAWAAN, STATUS_KAMAR, cx, rupiah } from '../../../lib/utils';

const KOSONG = { id: null, nomor_kamar: '', biaya_sewa: '', status_kamar: 'kosong', detail_kamar: '', fasilitas: [], foto_kamar: null, hapus_foto: false };

function FormKamar({ kamar, fasilitasList, batas, onSelesai }) {
    const edit = Boolean(kamar?.id);
    const form = useForm({
        nomor_kamar: kamar?.nomor ?? '',
        biaya_sewa: kamar?.biaya ?? '',
        status_kamar: kamar?.status ?? 'kosong',
        detail_kamar: kamar?.detail ?? '',
        fasilitas: kamar?.fasilitas?.map((f) => f.id) ?? [],
        foto_kamar: null,
        hapus_foto: false,
    });

    const toggle = (id) => form.setData('fasilitas', form.data.fasilitas.includes(id) ? form.data.fasilitas.filter((x) => x !== id) : [...form.data.fasilitas, id]);

    const kirim = (e) => {
        e.preventDefault();
        const opsi = { forceFormData: true, preserveScroll: true, onSuccess: onSelesai };
        if (edit) {
            form.transform((d) => ({ ...d, _method: 'put' })).post(`/admin/kamar/${kamar.id}`, opsi);
        } else {
            form.post('/admin/kamar', opsi);
        }
    };

    return (
        <form onSubmit={kirim} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
                <Kolom label="Nomor kamar" error={form.errors.nomor_kamar}>
                    <input value={form.data.nomor_kamar} onChange={(e) => form.setData('nomor_kamar', e.target.value)} required className="a-field" placeholder="mis. 101" />
                </Kolom>
                <Kolom label="Harga sewa / bulan (Rp)" error={form.errors.biaya_sewa}>
                    <input type="number" min="0" value={form.data.biaya_sewa} onChange={(e) => form.setData('biaya_sewa', e.target.value)} required className="a-field" placeholder="1250000" />
                </Kolom>
            </div>

            <Kolom label="Status kamar" error={form.errors.status_kamar}>
                <select value={form.data.status_kamar} onChange={(e) => form.setData('status_kamar', e.target.value)} className="a-field">
                    <option value="kosong">Tersedia (kosong)</option>
                    <option value="reservasi">Dipesan (ditahan calon penghuni)</option>
                    <option value="terisi">Terisi</option>
                </select>
            </Kolom>

            <Kolom label="Deskripsi kamar" error={form.errors.detail_kamar}>
                <textarea rows={3} value={form.data.detail_kamar} onChange={(e) => form.setData('detail_kamar', e.target.value)} className="a-field" placeholder="Ukuran, perabot, dan keunggulan kamar" />
            </Kolom>

            <Kolom label="Fasilitas kamar">
                {fasilitasList.length > 0 ? (
                    <div className="grid grid-cols-2 gap-2">
                        {fasilitasList.map((f) => (
                            <label key={f.id} className="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="checkbox" checked={form.data.fasilitas.includes(f.id)} onChange={() => toggle(f.id)} className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                {f.nama}
                            </label>
                        ))}
                    </div>
                ) : <p className="text-xs text-slate-400">Belum ada fasilitas. Tambahkan di menu Fasilitas.</p>}
            </Kolom>

            <UnggahFoto label="Foto kamar" url={kamar?.foto} file={form.data.foto_kamar} onPilih={(f) => form.setData('foto_kamar', f)}
                hapus={form.data.hapus_foto} onHapus={(v) => form.setData('hapus_foto', v)} error={form.errors.foto_kamar} batas={batas} />

            <div className="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" onClick={onSelesai} className="a-btn-outline">Batal</button>
                <button type="submit" disabled={form.processing} className="a-btn-primary">{form.processing ? 'Menyimpan...' : edit ? 'Simpan Perubahan' : 'Tambah Kamar'}</button>
            </div>
        </form>
    );
}

export default function Index({ kamars, fasilitasList, filters, batasUnggah }) {
    const [search, setSearch] = useState(filters.search);
    const [modal, setModal] = useState(null); // null | {kamar|null}
    const [hapus, setHapus] = useState(null);
    const awal = useRef(true);

    const terapkan = (ubah) => {
        const d = { search, status: filters.status, sort: filters.sort, ...ubah };
        router.get('/admin/kamar', {
            ...(d.search ? { search: d.search } : {}),
            ...(d.status && d.status !== 'all' ? { status: d.status } : {}),
            ...(d.sort ? { sort: d.sort } : {}),
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (awal.current) { awal.current = false; return undefined; }
        const t = setTimeout(() => terapkan({ search }), 400);
        return () => clearTimeout(t);
    }, [search]);

    const ubahStatus = (id, status) => router.patch(`/admin/kamar/${id}/status`, { status_kamar: status }, { preserveScroll: true });

    return (
        <div className="space-y-6">
            <Head title="Kelola Kamar" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-slate-500">{kamars.total} kamar terdaftar. Status dapat diubah langsung dari tabel.</p>
                <button onClick={() => setModal({ kamar: null })} className="a-btn-primary"><Plus size={16} /> Tambah Kamar</button>
            </div>

            <div className="a-card grid gap-3 p-4 sm:grid-cols-[1fr_180px_180px]">
                <div className="relative">
                    <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor kamar..." className="a-field !pl-10" />
                </div>
                <select value={filters.status} onChange={(e) => terapkan({ status: e.target.value })} className="a-field">
                    <option value="all">Semua status</option>
                    <option value="kosong">Tersedia</option>
                    <option value="reservasi">Dipesan</option>
                    <option value="terisi">Terisi</option>
                </select>
                <select value={filters.sort} onChange={(e) => terapkan({ sort: e.target.value })} className="a-field">
                    <option value="">Nomor kamar</option>
                    <option value="price_lowest">Harga terendah</option>
                    <option value="price_highest">Harga tertinggi</option>
                </select>
            </div>

            <div className="a-card overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-100/70 text-xs font-bold uppercase tracking-wider text-slate-600">
                                <th className="px-6 py-4">Kamar</th>
                                <th className="px-6 py-4">Harga / bulan</th>
                                <th className="px-6 py-4">Fasilitas</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-sm text-slate-700">
                            {kamars.data.length > 0 ? kamars.data.map((k) => (
                                <tr key={k.id} className="hover:bg-slate-50/80">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <img src={k.foto || FOTO_BAWAAN} alt="" className="h-12 w-16 rounded-lg border border-slate-200 object-cover" />
                                            <span className="font-bold text-slate-900">Kamar {k.nomor}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 font-semibold text-emerald-600">{rupiah(k.biaya)}</td>
                                    <td className="max-w-xs px-6 py-4">
                                        <div className="flex flex-wrap gap-1.5">
                                            {k.fasilitas.length > 0 ? k.fasilitas.map((f) => <span key={f.id} className="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{f.nama}</span>) : <span className="text-xs text-slate-400">-</span>}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <select value={k.status} onChange={(e) => ubahStatus(k.id, e.target.value)} aria-label={`Status kamar ${k.nomor}`}
                                            className={cx('cursor-pointer rounded-full border px-3 py-1.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/30', STATUS_KAMAR[k.status].admin)}>
                                            <option value="kosong">Tersedia</option>
                                            <option value="reservasi">Dipesan</option>
                                            <option value="terisi">Terisi</option>
                                        </select>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center justify-center gap-1">
                                            <button onClick={() => setModal({ kamar: k })} className="rounded-lg p-2 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600" title="Ubah"><Pencil size={16} /></button>
                                            <button onClick={() => setHapus(k)} className="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Hapus"><Trash2 size={16} /></button>
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan={5} className="py-14 text-center text-slate-400">Tidak ada kamar yang cocok.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <Paginasi links={kamars.links} />
            </div>

            <Modal buka={modal !== null} onTutup={() => setModal(null)} judul={modal?.kamar ? `Ubah Kamar ${modal.kamar.nomor}` : 'Tambah Kamar Baru'} lebar="max-w-2xl">
                {modal !== null && <FormKamar key={modal.kamar?.id ?? 'baru'} kamar={modal.kamar} fasilitasList={fasilitasList} batas={batasUnggah} onSelesai={() => setModal(null)} />}
            </Modal>

            <Konfirmasi buka={hapus !== null} onTutup={() => setHapus(null)} bahaya judul="Hapus kamar?" tombol="Hapus"
                pesan={`Kamar ${hapus?.nomor} akan dihapus. Kamar yang sudah punya riwayat booking/sewa tidak bisa dihapus (ubah statusnya saja).`}
                onYa={() => router.delete(`/admin/kamar/${hapus.id}`, { preserveScroll: true, onFinish: () => setHapus(null) })} />
        </div>
    );
}

Index.layout = (page) => <AdminLayout title="Kelola Kamar">{page}</AdminLayout>;
