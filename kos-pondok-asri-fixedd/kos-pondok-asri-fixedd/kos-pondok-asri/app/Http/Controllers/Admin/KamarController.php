<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KamarRequest;
use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Support\Media;
use App\Support\Tampil;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KamarController extends Controller
{
    public function index(Request $request)
    {
        $query = Kamar::with('fasilitas');

        if ($request->filled('search')) {
            $query->where('nomor_kamar', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status_kamar', $request->status);
        }

        match ($request->sort) {
            'price_lowest'  => $query->orderBy('biaya_sewa', 'asc'),
            'price_highest' => $query->orderBy('biaya_sewa', 'desc'),
            default         => $query->orderBy('nomor_kamar', 'asc'),
        };

        return Inertia::render('Admin/Kamar/Index', [
            'kamars'        => $query->paginate(8)->withQueryString()->through(fn ($k) => Tampil::kamar($k)),
            'batasUnggah'   => Media::batasUnggah(),
            'fasilitasList' => Fasilitas::orderBy('nama_fasilitas')->get()->map(fn ($f) => Tampil::fasilitas($f)),
            'filters'       => [
                'search' => (string) $request->search,
                'status' => $request->status ?: 'all',
                'sort'   => (string) $request->sort,
            ],
        ]);
    }

    public function store(KamarRequest $request)
    {
        $v = $request->validated();

        $kamar = Kamar::create([
            'nomor_kamar'  => $v['nomor_kamar'],
            'biaya_sewa'   => $v['biaya_sewa'],
            'detail_kamar' => $v['detail_kamar'] ?? null,
            'status_kamar' => $v['status_kamar'],
            'foto_kamar'   => $request->hasFile('foto_kamar') ? Media::simpan($request->file('foto_kamar'), 'kamar') : null,
        ]);

        $kamar->fasilitas()->sync($v['fasilitas'] ?? []);

        return redirect()->route('admin.kamar.index')->with('success', 'Kamar ' . $kamar->nomor_kamar . ' berhasil ditambahkan.');
    }

    public function update(KamarRequest $request, $id)
    {
        $kamar = Kamar::findOrFail($id);
        $v = $request->validated();

        $foto = $kamar->foto_kamar;
        if ($request->hasFile('foto_kamar')) {
            $foto = Media::simpan($request->file('foto_kamar'), 'kamar', $kamar->foto_kamar);
        } elseif ($request->boolean('hapus_foto')) {
            Media::hapus($kamar->foto_kamar);
            $foto = null;
        }

        $kamar->update([
            'nomor_kamar'  => $v['nomor_kamar'],
            'biaya_sewa'   => $v['biaya_sewa'],
            'detail_kamar' => $v['detail_kamar'] ?? null,
            'status_kamar' => $v['status_kamar'],
            'foto_kamar'   => $foto,
        ]);

        $kamar->fasilitas()->sync($v['fasilitas'] ?? []);

        return redirect()->route('admin.kamar.index')->with('success', 'Kamar ' . $kamar->nomor_kamar . ' berhasil diperbarui.');
    }

    /**
     * Ubah cepat status kamar (kosong / reservasi / terisi).
     */
    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status_kamar' => ['required', Rule::in([Kamar::KOSONG, Kamar::RESERVASI, Kamar::TERISI])],
        ]);

        $kamar = Kamar::findOrFail($id);
        $kamar->update($data);

        return back()->with('success', 'Status kamar ' . $kamar->nomor_kamar . ' diubah menjadi ' . $data['status_kamar'] . '.');
    }

    public function destroy($id)
    {
        $kamar = Kamar::findOrFail($id);

        // Kamar yang sudah punya riwayat booking/sewa tidak boleh dihapus (FK restrict)
        if ($kamar->bookings()->exists() || $kamar->sewaKamars()->exists()) {
            return redirect()->route('admin.kamar.index')
                ->with('error', 'Kamar ' . $kamar->nomor_kamar . ' tidak dapat dihapus karena memiliki riwayat booking/sewa. Ubah statusnya saja.');
        }

        Media::hapus($kamar->foto_kamar);
        $kamar->fasilitas()->detach();
        $kamar->delete();

        return redirect()->route('admin.kamar.index')->with('success', 'Kamar ' . $kamar->nomor_kamar . ' berhasil dihapus.');
    }
}
