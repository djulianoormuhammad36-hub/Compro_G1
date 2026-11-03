import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Plus, Trash2, UserRound } from 'lucide-react';
import AdminLayout from '../../../components/admin/AdminLayout';
import { Konfirmasi, Kolom, Lencana, Modal } from '../../../components/admin/Bagian';

function Sandi({ form, label = 'Password baru', nama = 'password' }) {
    return (
        <>
            <Kolom label={label} error={form.errors[nama]}>
                <input type="password" value={form.data[nama]} onChange={(e) => form.setData(nama, e.target.value)} required minLength={8} autoComplete="new-password" className="a-field" placeholder="Minimal 8 karakter" />
            </Kolom>
            <Kolom label="Ulangi password" error={form.errors.password_confirmation}>
                <input type="password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} required autoComplete="new-password" className="a-field" />
            </Kolom>
        </>
    );
}

function Aksi({ onBatal, proses, tombol }) {
    return (
        <div className="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <button type="button" onClick={onBatal} className="a-btn-outline">Batal</button>
            <button type="submit" disabled={proses} className="a-btn-primary">{tombol}</button>
        </div>
    );
}

function FormTambah({ onSelesai }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post('/admin/akun', { preserveScroll: true, onSuccess: onSelesai }); }} className="space-y-4">
            <Kolom label="Nama" error={form.errors.name}><input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required autoFocus className="a-field" /></Kolom>
            <Kolom label="Email (untuk login)" error={form.errors.email}><input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required className="a-field" /></Kolom>
            <Sandi form={form} label="Password" />
            <Aksi onBatal={onSelesai} proses={form.processing} tombol="Buat Akun" />
        </form>
    );
}

function FormGanti({ onSelesai }) {
    const form = useForm({ password_lama: '', password: '', password_confirmation: '' });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.put('/admin/akun/password', { preserveScroll: true, onSuccess: onSelesai }); }} className="space-y-4">
            <Kolom label="Password lama" error={form.errors.password_lama}>
                <input type="password" value={form.data.password_lama} onChange={(e) => form.setData('password_lama', e.target.value)} required autoComplete="current-password" className="a-field" />
            </Kolom>
            <Sandi form={form} />
            <Aksi onBatal={onSelesai} proses={form.processing} tombol="Ganti Password" />
        </form>
    );
}

function FormReset({ akun, onSelesai }) {
    const form = useForm({ password: '', password_confirmation: '' });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.put(`/admin/akun/${akun.id}/reset`, { preserveScroll: true, onSuccess: onSelesai }); }} className="space-y-4">
            <p className="text-sm text-slate-500">Atur password baru untuk <b className="text-slate-800">{akun.email}</b>. Berikan password ini kepada yang bersangkutan.</p>
            <Sandi form={form} />
            <Aksi onBatal={onSelesai} proses={form.processing} tombol="Atur Ulang" />
        </form>
    );
}

export default function Index({ akun }) {
    const [dialog, setDialog] = useState(null); // {jenis:'tambah'|'ganti'|'reset'|'hapus', akun?}
    const tutup = () => setDialog(null);

    return (
        <div className="space-y-6">
            <Head title="Akun Admin" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-slate-500">{akun.length} akun admin. Hanya akun di sini yang bisa masuk ke panel.</p>
                <div className="flex gap-3">
                    <button onClick={() => setDialog({ jenis: 'ganti' })} className="a-btn-outline"><KeyRound size={16} /> Ganti password saya</button>
                    <button onClick={() => setDialog({ jenis: 'tambah' })} className="a-btn-primary"><Plus size={16} /> Tambah Admin</button>
                </div>
            </div>

            <div className="a-card overflow-hidden">
                <ul className="divide-y divide-slate-100">
                    {akun.map((a) => (
                        <li key={a.id} className="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                            <div className="flex items-center gap-4">
                                <span className="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500"><UserRound size={18} /></span>
                                <div>
                                    <p className="font-bold text-slate-900">{a.nama} {a.saya && <Lencana warna="hijau" className="ml-2 !px-2 !py-0.5 !text-[10px]">Anda</Lencana>}</p>
                                    <p className="text-sm text-slate-500">{a.email}</p>
                                    <p className="text-xs text-slate-400">Dibuat {a.dibuat}</p>
                                </div>
                            </div>
                            {!a.saya && (
                                <div className="flex gap-2">
                                    <button onClick={() => setDialog({ jenis: 'reset', akun: a })} className="a-btn-outline !px-3 !py-2 !text-xs"><KeyRound size={14} /> Atur ulang password</button>
                                    <button onClick={() => setDialog({ jenis: 'hapus', akun: a })} className="a-btn-danger !px-3 !py-2 !text-xs"><Trash2 size={14} /> Hapus</button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            </div>

            <Modal buka={dialog?.jenis === 'tambah'} onTutup={tutup} judul="Tambah Akun Admin" lebar="max-w-md">{dialog?.jenis === 'tambah' && <FormTambah onSelesai={tutup} />}</Modal>
            <Modal buka={dialog?.jenis === 'ganti'} onTutup={tutup} judul="Ganti Password Saya" lebar="max-w-md">{dialog?.jenis === 'ganti' && <FormGanti onSelesai={tutup} />}</Modal>
            <Modal buka={dialog?.jenis === 'reset'} onTutup={tutup} judul="Atur Ulang Password" lebar="max-w-md">{dialog?.jenis === 'reset' && <FormReset key={dialog.akun.id} akun={dialog.akun} onSelesai={tutup} />}</Modal>
            <Konfirmasi buka={dialog?.jenis === 'hapus'} onTutup={tutup} bahaya judul="Hapus akun admin?" tombol="Hapus"
                pesan={`Akun ${dialog?.akun?.email ?? ''} tidak bisa lagi masuk ke panel admin.`}
                onYa={() => router.delete(`/admin/akun/${dialog.akun.id}`, { preserveScroll: true, onFinish: tutup })} />
        </div>
    );
}

Index.layout = (page) => <AdminLayout title="Akun Admin">{page}</AdminLayout>;