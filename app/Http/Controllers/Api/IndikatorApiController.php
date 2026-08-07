<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BuildsTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndikatorRequest;
use App\Models\Indikator;
use App\Models\IndikatorNilai;
use App\Models\IndikatorPeriode;
use App\Models\Sidebar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IndikatorApiController extends Controller
{
    use BuildsTableQuery;

    /** Menu lengkap sidebar > subsidebar > indikator, untuk navigasi frontend. */
    public function menu()
    {
        $sidebars = Sidebar::with('subsidebars.indikators:id,subsidebar_id,nama_judul,slug,satuan')
            ->orderBy('urutan')
            ->get(['id', 'nama', 'slug', 'urutan']);

        return response()->json($sidebars);
    }

    /**
     * Daftar indikator untuk halaman "Kelola Data" (search + sort + pagination, lihat
     * BuildsTableQuery), lengkap dengan nama kategori/sidebar induk & jumlah periode
     * yang sudah pernah diupload. Boleh diakses siapa saja yang sudah login (read-only),
     * sama seperti /api/users -- tulis (store/update/destroy) dibatasi permission "data.manage".
     *
     * Query string: ?search=...&subsidebar_id=...&sort_by=nama_judul|satuan|urutan|created_at
     * &sort_dir=asc|desc&per_page=10|25|50|100&page=1
     */
    public function index(Request $request)
    {
        $query = Indikator::query()
            ->with('subsidebar:id,nama,sidebar_id', 'subsidebar.sidebar:id,nama')
            ->withCount('periode');

        if ($request->filled('subsidebar_id')) {
            $query->where('subsidebar_id', $request->integer('subsidebar_id'));
        }

        $indikators = $this->paginateTable($query, $request, [
            'searchable' => ['nama_judul', 'satuan'],
            'sortable' => ['nama_judul', 'satuan', 'urutan', 'created_at'],
            'default_sort' => 'nama_judul',
            'default_dir' => 'asc',
        ]);

        $indikators->through(fn (Indikator $indikator) => $this->formatIndikator($indikator));

        return response()->json($indikators);
    }

    /** Daftar tahun/triwulan yang tersedia untuk satu indikator (dipakai untuk isi pilihan tahun di frontend). */
    public function periode(Indikator $indikator)
    {
        $periode = $indikator->periode()
            ->orderBy('tahun')
            ->orderBy('triwulan')
            ->get(['id', 'tahun', 'triwulan']);

        return response()->json($periode);
    }

    /**
     * Data tabel/time-series untuk satu indikator, difilter tahun tertentu.
     * Contoh: GET /api/indikators/jumlah-guru-ma/data?tahun[]=2023&tahun[]=2024&tahun[]=2025
     */
    public function data(Indikator $indikator, Request $request)
    {
        $tahunDipilih = array_map('intval', (array) $request->query('tahun', []));

        $kolom = $indikator->kolom()->get(['id', 'kolom_key', 'kolom_label', 'induk_label']);
        $baris = $indikator->baris()->orderBy('urutan')->get(['id', 'baris_label', 'kecamatan_id']);

        $periodeQuery = $indikator->periode();
        if (! empty($tahunDipilih)) {
            $periodeQuery->whereIn('tahun', $tahunDipilih);
        }
        $periode = $periodeQuery->orderBy('tahun')->orderBy('triwulan')->get(['id', 'tahun', 'triwulan']);

        $nilai = IndikatorNilai::whereIn('periode_id', $periode->pluck('id'))
            ->get(['baris_id', 'periode_id', 'kolom_id', 'nilai', 'nilai_numerik']);

        // Bentuk output flat, biar fleksibel dipivot di sisi frontend sesuai kebutuhan (tabel atau chart)
        $data = $nilai->map(function ($n) use ($periode, $baris, $kolom) {
            return [
                'baris' => $baris->firstWhere('id', $n->baris_id)?->baris_label,
                'tahun' => $periode->firstWhere('id', $n->periode_id)?->tahun,
                'triwulan' => $periode->firstWhere('id', $n->periode_id)?->triwulan,
                'kolom' => $kolom->firstWhere('id', $n->kolom_id)?->kolom_label,
                'nilai' => $n->nilai,
                'nilai_numerik' => $n->nilai_numerik,
            ];
        });

        return response()->json([
            'indikator' => [
                'nama_judul' => $indikator->nama_judul,
                'satuan' => $indikator->satuan,
            ],
            'kolom' => $kolom,
            'baris' => $baris,
            'periode' => $periode,
            'data' => $data,
        ]);
    }

    /**
     * Tambah indikator baru (metadata saja -- nama judul, kategori, satuan, dst). Isi
     * data/nilai per periode tetap lewat menu upload Excel terpisah (lihat UploadApiController).
     * KHUSUS permission "data.manage" (ipds, admin, superadmin secara default).
     */
    public function store(StoreIndikatorRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->buatSlugUnik($data['nama_judul']);
        $data['urutan'] = $data['urutan'] ?? ((int) Indikator::where('subsidebar_id', $data['subsidebar_id'])->max('urutan') + 1);

        $indikator = Indikator::create($data);

        return response()->json([
            'message' => "Indikator \"{$indikator->nama_judul}\" berhasil ditambahkan.",
            'indikator' => $this->formatIndikator($indikator->fresh()->load('subsidebar.sidebar')),
        ], 201);
    }

    /**
     * Update metadata indikator (nama judul, kategori, satuan, urutan tampil). KHUSUS
     * permission "data.manage" -- dibatasi lewat middleware EnsureHasPermission di
     * routes/api.php, bukan di sini.
     */
    public function update(Indikator $indikator, Request $request)
    {
        $data = $request->validate([
            'subsidebar_id' => ['sometimes', 'required', 'integer', 'exists:subsidebars,id'],
            'nama_judul' => ['sometimes', 'required', 'string', 'max:255'],
            'satuan' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tipe_baris' => ['sometimes', 'nullable', 'string', 'max:50'],
            'urutan' => ['sometimes', 'integer', 'min:0'],
        ]);

        // Slug dibuat ulang hanya kalau judulnya berubah, supaya tidak mengubah URL yang
        // sudah dipakai/dibagikan kalau cuma kategori atau urutan yang diedit.
        if (isset($data['nama_judul']) && $data['nama_judul'] !== $indikator->nama_judul) {
            $data['slug'] = $this->buatSlugUnik($data['nama_judul'], $indikator->id);
        }

        $indikator->update($data);

        return response()->json([
            'message' => "Indikator \"{$indikator->nama_judul}\" berhasil diperbarui.",
            'indikator' => $this->formatIndikator($indikator->fresh()->load('subsidebar.sidebar')),
        ]);
    }

    /**
     * Hapus satu indikator beserta seluruh kolom/baris/periode/nilainya (cascade via
     * foreign key di migration). KHUSUS permission "data.manage".
     */
    public function destroy(Indikator $indikator)
    {
        $nama = $indikator->nama_judul;
        $indikator->delete();

        return response()->json([
            'message' => "Indikator \"{$nama}\" beserta seluruh datanya berhasil dihapus.",
        ]);
    }

    /**
     * Hapus satu periode (tahun/triwulan) saja, indikatornya tetap ada. Versi API dari
     * IndikatorController::hapusPeriode() (web) -- dipakai supaya mobile app & tombol
     * hapus di web bisa sama-sama lewat jalur API yang RBAC-nya konsisten. KHUSUS
     * permission "data.manage".
     */
    public function hapusPeriode(Indikator $indikator, IndikatorPeriode $periode)
    {
        abort_unless($periode->indikator_id === $indikator->id, 404);

        $labelPeriode = $periode->tahun.($periode->triwulan ? ' triwulan '.$periode->triwulan : '');

        $periode->nilai()->delete();
        $periode->delete();

        return response()->json([
            'message' => "Data periode {$labelPeriode} untuk indikator \"{$indikator->nama_judul}\" berhasil dihapus.",
        ]);
    }

    private function buatSlugUnik(string $namaJudul, ?int $abaikanId = null): string
    {
        $base = Str::slug($namaJudul);
        $slug = $base;
        $i = 2;

        while (
            Indikator::where('slug', $slug)
                ->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function formatIndikator(Indikator $indikator): array
    {
        return [
            'id' => $indikator->id,
            'nama_judul' => $indikator->nama_judul,
            'slug' => $indikator->slug,
            'satuan' => $indikator->satuan,
            'tipe_baris' => $indikator->tipe_baris,
            'urutan' => $indikator->urutan,
            'periode_count' => $indikator->periode_count ?? $indikator->periode()->count(),
            'subsidebar' => $indikator->subsidebar ? [
                'id' => $indikator->subsidebar->id,
                'nama' => $indikator->subsidebar->nama,
                'sidebar' => $indikator->subsidebar->sidebar?->nama,
            ] : null,
        ];
    }
}
