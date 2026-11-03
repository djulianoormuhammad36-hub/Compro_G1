<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FasilitasRequest;
use App\Models\Fasilitas;
use Inertia\Inertia;

class FasilitasController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Fasilitas/Index', [
            'fasilitas' => Fasilitas::withCount('kamars')->orderBy('nama_fasilitas')->get()->map(fn ($f) => [
                'id'        => $f->id_fasilitas,
                'nama'      => $f->nama_fasilitas,
                'deskripsi' => $f->deskripsi_fasilitas,
                'ikon'      => $f->icon,
                'dipakai'   => $f->kamars_count,
            ]),
        ]);
    }

    public function store(FasilitasRequest $request)
    {
        $f = Fasilitas::create($request->validated());

        return redirect()->route('admin.fasilitas.index')->with('success', 'Fasilitas "' . $f->nama_fasilitas . '" berhasil ditambahkan.');
    }

    public function update(FasilitasRequest $request, $id)
    {
        $f = Fasilitas::findOrFail($id);
        $f->update($request->validated());

        return redirect()->route('admin.fasilitas.index')->with('success', 'Fasilitas "' . $f->nama_fasilitas . '" berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $f = Fasilitas::findOrFail($id);
        $f->kamars()->detach(); // dilepas dari semua kamar
        $f->delete();

        return redirect()->route('admin.fasilitas.index')->with('success', 'Fasilitas "' . $f->nama_fasilitas . '" dihapus.');
    }
}
