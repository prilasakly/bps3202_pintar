<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait generik buat endpoint API yang nampilin data dalam jumlah banyak (tabel):
 * search, sort, dan pagination dengan pilihan jumlah baris per halaman -- tinggal
 * pakai di controller mana pun tanpa nulis ulang logikanya tiap kali.
 *
 * Cara pakai di controller API:
 *
 *   use App\Http\Controllers\Concerns\BuildsTableQuery;
 *
 *   class ContohApiController extends Controller
 *   {
 *       use BuildsTableQuery;
 *
 *       public function index(Request $request)
 *       {
 *           $query = Model::query()->with('relasi');
 *
 *           $hasil = $this->paginateTable($query, $request, [
 *               'searchable'       => ['kolom_a', 'kolom_b'],   // dicari pakai LIKE %kata%
 *               'search_relations' => ['relasi' => ['kolom_c']], // search tembus ke relasi (opsional)
 *               'sortable'         => ['kolom_a', 'kolom_b', 'created_at'], // whitelist kolom sort
 *               'default_sort'     => 'kolom_a',
 *               'default_dir'      => 'asc',
 *           ]);
 *
 *           // Kalau butuh transform row (misal format relasi), pakai getCollection()->transform()
 *           // SEBELUM di-return, jangan bikin Collection/array baru -- supaya meta paginationnya
 *           // (current_page, last_page, total, dst) tetap ikut ke response JSON.
 *           $hasil->getCollection()->transform(fn ($item) => [...]);
 *
 *           return response()->json($hasil);
 *       }
 *   }
 *
 * Query string yang dikenali di endpoint-nya:
 *   ?search=kata-kunci
 *   &sort_by=kolom_a&sort_dir=asc|desc
 *   &per_page=10|25|50|100   (default ambil dari per_page_options[0] / default_per_page)
 *   &page=1
 *
 * Response JSON-nya otomatis bentuk paginator standar Laravel (data, current_page,
 * last_page, per_page, total, from, to, ...) -- persis yang dipahami sama mixin
 * JS `pintarTableFactory` di layouts/app.blade.php, jadi tinggal disambungkan.
 */
trait BuildsTableQuery
{
    /**
     * @param  array{
     *     searchable?: array<int, string>,
     *     search_relations?: array<string, array<int, string>>,
     *     sortable?: array<int, string>,
     *     default_sort?: string|null,
     *     default_dir?: string,
     *     per_page_options?: array<int, int>,
     *     default_per_page?: int,
     * }  $config
     */
    protected function paginateTable(Builder $query, Request $request, array $config = []): LengthAwarePaginator
    {
        $searchable = $config['searchable'] ?? [];
        $searchRelations = $config['search_relations'] ?? [];
        $sortable = $config['sortable'] ?? $searchable;
        $defaultSort = $config['default_sort'] ?? ($sortable[0] ?? null);
        $defaultDir = ($config['default_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPageOptions = $config['per_page_options'] ?? [10, 25, 50, 100];
        $defaultPerPage = $config['default_per_page'] ?? $perPageOptions[0];

        $search = trim((string) $request->query('search', ''));
        if ($search !== '' && (! empty($searchable) || ! empty($searchRelations))) {
            $query->where(function (Builder $q) use ($search, $searchable, $searchRelations) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
                foreach ($searchRelations as $relation => $columns) {
                    $q->orWhereHas($relation, function (Builder $relationQuery) use ($columns, $search) {
                        $relationQuery->where(function (Builder $inner) use ($columns, $search) {
                            foreach ($columns as $column) {
                                $inner->orWhere($column, 'like', "%{$search}%");
                            }
                        });
                    });
                }
            });
        }

        $sortBy = $request->query('sort_by');
        if (! is_string($sortBy) || ! in_array($sortBy, $sortable, true)) {
            $sortBy = $defaultSort;
        }
        $sortDir = strtolower((string) $request->query('sort_dir', $defaultDir)) === 'desc' ? 'desc' : 'asc';

        if ($sortBy) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->query('per_page', $defaultPerPage);
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = $defaultPerPage;
        }

        return $query->paginate($perPage)->appends($request->query());
    }
}
