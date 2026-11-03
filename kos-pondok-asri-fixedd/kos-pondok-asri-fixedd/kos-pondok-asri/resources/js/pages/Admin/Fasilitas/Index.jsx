import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, CircleCheck, Wifi, SquareParking, CookingPot, Shirt, Snowflake, ShowerHead, ShieldCheck } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Konfirmasi, Kolom, Modal } from '../../../components/admin/Bagian';

const IKON = {
    'fa-wifi': Wifi, 'fa-square-parking': SquareParking, 'fa-kitchen-set': CookingPot, 'fa-shirt': Shirt,
    'fa-snowflake': Snowflake, 'fa-shower': ShowerHead, 'fa-shield-halved': ShieldCheck,
};

function FormFasilitas({ item, onSelesai }) {
    const edit = Boolean(item?.id);
    const form = useForm({ nama_fasilitas: item?.nama ?? '', deskripsi_fasilitas: item?.deskripsi ?? '' });

    const kirim = (e) => {
        e.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: onSelesai };
        if (edit) form.put(`/admin/fasilitas/${item.id}`, opsi);
        else form.post('/admin/fasilitas', opsi);
    };

    return (
        <form onSubmit={kirim} className="space-y-4">
            <Kolom label="Nama fasilitas" error={form.errors.nama_fasilitas}>
                <input value={form.data.nama_fasilitas} onChange={(e) => form.setData('nama_fasilitas', e.target.value)} required autoFocus className="a-field" placeholder="mis. Wi-Fi, Dapur Bersama, AC" />
            </Kolom>
            <Kolom label="Deskripsi singkat" error={form.errors.deskripsi_fasilitas}>
                <textarea rows={3} value={form.data.deskripsi_fasilitas} onChange={(e) => form.setData('deskripsi_fasilitas', e.target.value)} className="a-field" placeholder="Penjelasan yang tampil di website" />
            </Kolom>
            <div className="flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" onClick={onSelesai} className="a-btn-outline">Batal</button>
                <button type="submit" disabled={form.processing} className="a-btn-primary">{edit ? 'Simpan Perubahan' : 'Tambah Fasilitas'}</button>
            </div>
        </form>
    );
}

export default function Index({ fasilitas }) {
    const [modal, setModal] = useState(null);
    const [hapus, setHapus] = useState(null);

    return (
        <div className="space-y-6">
            <Head title="Fasilitas" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-slate-500">{fasilitas.length} fasilitas. Fasilitas dipilih per kamar di menu Kelola Kamar.</p>
                <button onClick={() => setModal({ item: null })} className="a-btn-primary"><Plus size={16} /> Tambah Fasilitas</button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {fasilitas.length > 0 ? fasilitas.map((f) => {
                    const Ikon = IKON[f.ikon] ?? CircleCheck;
                    return (
                        <div key={f.id} className="a-card flex flex-col p-5">
                            <div className="flex items-start gap-4">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"><Ikon size={18} /></span>
                                <div className="min-w-0 flex-1">
                                    <h3 className="font-bold text-slate-900">{f.nama}</h3>
                                    <p className="mt-1 line-clamp-3 text-sm text-slate-500">{f.deskripsi || <span className="italic text-slate-400">Tanpa deskripsi</span>}</p>
                                </div>
                            </div>
                            <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                <span className="text-xs text-slate-400">Dipakai di {f.dipakai} kamar</span>
                                <div className="flex gap-1">
                                    <button onClick={() => setModal({ item: f })} className="rounded-lg p-2 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600" title="Ubah"><Pencil size={15} /></button>
                                    <button onClick={() => setHapus(f)} className="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Hapus"><Trash2 size={15} /></button>
                                </div>
                            </div>
                        </div>
                    );
                }) : <p className="a-card col-span-full p-12 text-center text-sm text-slate-400">Belum ada fasilitas. Klik "Tambah Fasilitas" untuk memulai.</p>}
            </div>

            <Modal buka={modal !== null} onTutup={() => setModal(null)} judul={modal?.item ? 'Ubah Fasilitas' : 'Tambah Fasilitas'}>
                {modal !== null && <FormFasilitas key={modal.item?.id ?? 'baru'} item={modal.item} onSelesai={() => setModal(null)} />}
            </Modal>

            <Konfirmasi buka={hapus !== null} onTutup={() => setHapus(null)} bahaya judul="Hapus fasilitas?" tombol="Hapus"
                pesan={`"${hapus?.nama}" akan dihapus dan dilepas dari ${hapus?.dipakai ?? 0} kamar yang memakainya.`}
                onYa={() => router.delete(`/admin/fasilitas/${hapus.id}`, { preserveScroll: true, onFinish: () => setHapus(null) })} />
        </div>
    );
}

Index.layout = (page) => <AdminLayout title="Fasilitas">{page}</AdminLayout>;
