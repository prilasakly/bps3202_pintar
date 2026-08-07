<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubsidebarRequest;
use App\Http\Requests\UpdateSubsidebarRequest;
use App\Models\Subsidebar;
use Illuminate\Support\Str;

/**
 * CRUD kategori (subsidebar) untuk halaman "Kelola Data". Baca (index) boleh siapa saja
 * yang sudah login (sama seperti daftar user), tulis (store/update/destroy) dibatasi
 * permission "data.manage" -- lihat pengelompokan middleware di routes/api.php.
 */
class SubsidebarApiController extends Controller
{
    /** Daftar semua subsidebar beserta sidebar induk & jumlah indikatornya, dikelompokkan per sidebar. */
    public function index()
    {
        $subsidebars = Subsidebar::with('sidebar:id,nama')
            ->withCount('indikators')
            ->orderBy('sidebar_id')
            ->orderBy('urutan')
            ->get();

        return response()->json($subsidebars);
    }

    public function store(StoreSubsidebarRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->buatSlugUnik($data['sidebar_id'], $data['nama']);
        $data['urutan'] = $data['urutan'] ?? ((int) Subsidebar::where('sidebar_id', $data['sidebar_id'])->max('urutan') + 1);

        $subsidebar = Subsidebar::create($data);

        return response()->json([
            'message' => "Kategori \"{$subsidebar->nama}\" berhasil ditambahkan.",
            'subsidebar' => $subsidebar->fresh()->load('sidebar:id,nama'),
        ], 201);
    }

    public function update(UpdateSubsidebarRequest $request, Subsidebar $subsidebar)
    {
        $data = $request->validated();

        // Slug dibuat ulang hanya kalau nama atau induknya (sidebar_id) berubah, supaya
        // tidak mengubah URL/relasi yang sudah dipakai kalau cuma urutan yang diedit.
        if (isset($data['nama']) || isset($data['sidebar_id'])) {
            $sidebarId = $data['sidebar_id'] ?? $subsidebar->sidebar_id;
            $nama = $data['nama'] ?? $subsidebar->nama;
            $data['slug'] = $this->buatSlugUnik($sidebarId, $nama, $subsidebar->id);
        }

        $subsidebar->update($data);

        return response()->json([
            'message' => "Kategori \"{$subsidebar->nama}\" berhasil diperbarui.",
            'subsidebar' => $subsidebar->fresh()->load('sidebar:id,nama'),
        ]);
    }

    /** Hapus kategori. Ditolak kalau masih ada indikator di dalamnya, supaya tidak menghapus data secara tidak sengaja. */
    public function destroy(Subsidebar $subsidebar)
    {
        $jumlahIndikator = $subsidebar->indikators()->count();

        if ($jumlahIndikator > 0) {
            abort(422, "Kategori \"{$subsidebar->nama}\" masih memiliki {$jumlahIndikator} indikator. Pindahkan atau hapus indikatornya dulu sebelum menghapus kategori ini.");
        }

        $nama = $subsidebar->nama;
        $subsidebar->delete();

        return response()->json([
            'message' => "Kategori \"{$nama}\" berhasil dihapus.",
        ]);
    }

    private function buatSlugUnik(int $sidebarId, string $nama, ?int $abaikanId = null): string
    {
        $base = Str::slug($nama);
        $slug = $base;
        $i = 2;

        while (
            Subsidebar::where('sidebar_id', $sidebarId)
                ->where('slug', $slug)
                ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
