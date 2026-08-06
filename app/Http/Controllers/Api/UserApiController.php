<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BuildsTableQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserExcelImporter;
use App\Services\UserExcelTemplateGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserApiController extends Controller
{
    use BuildsTableQuery;

    /**
     * Daftar user beserta role-nya, dengan search + sort + pagination (lihat
     * BuildsTableQuery). Bisa diakses semua role yang SUDAH LOGIN (read-only) --
     * dibatasi middleware auth:sanctum saja di routes/api.php, TANPA EnsureHasRole,
     * karena "semua yang login boleh lihat" (lihat permintaan RBAC).
     *
     * Query string: ?search=...&sort_by=name|email|nip_lama|nip_baru|golongan|jabatan|created_at
     * &sort_dir=asc|desc&per_page=10|25|50|100&page=1
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,nama,slug');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
        $users = $this->paginateTable($query, $request, [
            'searchable' => ['name', 'email', 'nip_lama', 'nip_baru', 'golongan', 'jabatan'],
            'search_relations' => ['roles' => ['nama', 'slug']],
            'sortable' => ['name', 'email', 'nip_lama', 'nip_baru', 'golongan', 'jabatan', 'created_at'],
            'default_sort' => 'name',
            'default_dir' => 'asc',
        ]);

        // Now IDE knows getCollection() exists!
        $users->getCollection()->transform(fn (User $user) => $this->formatUser($user));

        return response()->json($users);
    }

    /**
     * Tambah user baru. KHUSUS role "superadmin" -- dibatasi middleware EnsureHasRole
     * di routes/api.php, bukan di sini.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'nip_lama' => $request->input('nip_lama'),
            'nip_baru' => $request->input('nip_baru'),
            'golongan' => $request->input('golongan'),
            'jabatan' => $request->input('jabatan'),
        ]);

        $user->roles()->sync($request->input('roles', []));

        return response()->json([
            'message' => "User \"{$user->name}\" berhasil ditambahkan.",
            'user' => $this->formatUser($user->load('roles:id,nama,slug')),
        ], 201);
    }

    /**
     * Ubah data user (termasuk role-nya). KHUSUS role "superadmin".
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->only(['name', 'email', 'nip_lama', 'nip_baru', 'golongan', 'jabatan']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->roles()->sync($request->input('roles', []));
        }

        return response()->json([
            'message' => "User \"{$user->name}\" berhasil diperbarui.",
            'user' => $this->formatUser($user->fresh()->load('roles:id,nama,slug')),
        ]);
    }

    /**
     * Hapus user. KHUSUS role "superadmin". Superadmin tidak boleh menghapus akunnya
     * sendiri supaya tidak ada kondisi "tidak ada superadmin tersisa" secara tidak sengaja.
     */
    public function destroy(User $user)
    {
        if ($user->id === request()->user()->id) {
            abort(422, 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $nama = $user->name;
        $user->delete();

        return response()->json([
            'message' => "User \"{$nama}\" berhasil dihapus.",
        ]);
    }

    /**
     * Download template Excel untuk import user massal. KHUSUS role "superadmin"
     * -- satu grup middleware dengan store/update/destroy/import (lihat routes/api.php).
     */
    public function template(UserExcelTemplateGenerator $generator)
    {
        $spreadsheet = $generator->generate();
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template-import-user-pintar.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import user secara massal dari file Excel sesuai format template resmi
     * (lihat UserExcelTemplateGenerator & UserExcelImporter). KHUSUS role "superadmin".
     * Baris yang gagal tidak menggagalkan baris lain -- hasil per baris dilaporkan
     * di response supaya superadmin bisa perbaiki lalu upload ulang cuma baris yang gagal.
     */
    public function import(ImportUsersRequest $request, UserExcelImporter $importer)
    {
        $hasil = $importer->import($request->file('file')->getRealPath());

        $statusCode = match ($hasil['status']) {
            'sukses' => 201,
            'sebagian' => 200,
            default => 422,
        };

        return response()->json($hasil, $statusCode);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'nip_lama' => $user->nip_lama,
            'nip_baru' => $user->nip_baru,
            'golongan' => $user->golongan,
            'jabatan' => $user->jabatan,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => $role->id,
                'nama' => $role->nama,
                'slug' => $role->slug,
            ])->values(),
            'created_at' => $user->created_at?->toDateTimeString(),
        ];
    }
}