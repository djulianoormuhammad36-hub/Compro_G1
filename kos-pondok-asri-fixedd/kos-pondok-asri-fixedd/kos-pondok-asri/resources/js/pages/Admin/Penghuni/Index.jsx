import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Lencana, Paginasi } from '../../../components/admin/Bagian';

export default function Index({ penghunis, filters }) {
    const [search, setSearch] = useState(filters.search);
    const awal = useRef(true);

    const terapkan = (ubah) => {
        const d = { search, status: filters.status, ...ubah };
        router.get('/admin/penghuni', {
            ...(d.search ? { search: d.search } : {}),
            ...(d.status && d.status !== 'all' ? { status: d.status } : {}),
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (awal.current) { awal.current = false; return undefined; }
        const t = setTimeout(() => terapkan({ search }), 400);
        return () => clearTimeout(t);
    }, [search]);

    return (
        <div className="space-y-6">
            <Head title="Penghuni" />
            <p className="text-sm text-slate-500">{penghunis.total} data penghuni. Klik "Lihat" untuk melihat identitas lengkap, menandai keluar, atau menghapus data.</p>

            <div className="a-card grid gap-3 p-4 sm:grid-cols-[1fr_200px]">
                <div className="relative">
                    <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama atau nomor HP..." className="a-field !pl-10" />
                </div>
                <select value={filters.status} onChange={(e) => terapkan({ status: e.target.value })} className="a-field">
                    <option value="all">Semua</option>
                    <option value="aktif">Sedang ngekos</option>
                    <option value="nonaktif">Tidak aktif / calon</option>
                </select>
            </div>

            <div className="a-card overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left">
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-100/70 text-xs font-bold uppercase tracking-wider text-slate-600">
                                <th className="px-6 py-4">Nama</th>
                                <th className="px-6 py-4">Kontak</th>
                                <th className="px-6 py-4">Kamar</th>
                                <th className="px-6 py-4">Masuk</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-sm text-slate-700">
                            {penghunis.data.length > 0 ? penghunis.data.map((p) => (
                                <tr key={p.id} className="hover:bg-slate-50/80">
                                    <td className="px-6 py-4 font-semibold text-slate-900">{p.nama}</td>
                                    <td className="px-6 py-4">{p.kontak}</td>
                                    <td className="px-6 py-4">{p.kamar ? `Kamar ${p.kamar}` : '-'}</td>
                                    <td className="px-6 py-4 text-xs">{p.masuk ?? '-'}{p.keluar && <span className="block text-slate-400">keluar {p.keluar}</span>}</td>
                                    <td className="px-6 py-4"><Lencana warna={p.aktif ? 'hijau' : 'abu'}>{p.aktif ? 'Sedang ngekos' : 'Tidak aktif'}</Lencana></td>
                                    <td className="px-6 py-4 text-center">
                                        <Link href={`/admin/penghuni/${p.id}`} className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"><Eye size={14} /> Lihat</Link>
                                    </td>
                                </tr>
                            )) : <tr><td colSpan={6} className="py-14 text-center text-slate-400">Belum ada data penghuni.</td></tr>}
                        </tbody>
                    </table>
                </div>
                <Paginasi links={penghunis.links} />
            </div>
        </div>
    );
}

Index.layout = (page) => <AdminLayout title="Penghuni">{page}</AdminLayout>;
