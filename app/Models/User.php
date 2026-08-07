<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'nip_lama', 'nip_baru', 'golongan', 'jabatan'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Tim/role yang diikuti user ini. Many-to-many karena satu user bisa tergabung
     * di lebih dari satu tim (contoh: ipds + nerwilis).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Cek apakah user punya salah satu role yang diminta.
     * Contoh: $user->hasRole('ipds') atau $user->hasRole(['ipds', 'produksi']).
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Shortcut untuk cek role "superadmin" -- role tertinggi yang boleh
     * mengelola akun user lain (tambah/ubah/hapus) lewat menu "Kelola User".
     */
    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Semua slug permission yang dimiliki user ini lewat role-role-nya (gabungan,
     * tanpa duplikat). Dipakai di AuthApiController supaya dikirim ke frontend
     * (Alpine store "auth") dan dipakai di EnsureHasPermission middleware.
     *
     * @return array<int, string>
     */
    public function permissionSlugs(): array
    {
        return $this->roles()
            ->with('permissions:slug')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Cek apakah user punya permission tertentu (lewat salah satu role-nya).
     * BEDA dengan hasRole(): permission ini datanya 100% dari database (tabel
     * permissions + permission_role), jadi bisa diubah lewat halaman "Kelola Hak
     * Akses" tanpa ubah kode. Superadmin selalu dianggap punya semua permission,
     * supaya superadmin tidak bisa "mengunci dirinya sendiri" secara tidak sengaja.
     *
     * Contoh: $user->hasPermission('users.manage'), $user->hasPermission(['data.manage', 'data.upload']).
     *
     * (Sengaja dinamai hasPermission(), bukan can(), supaya tidak menimpa method can()
     * bawaan Laravel dari trait Authorizable yang dipakai sistem Gate/Policy.)
     */
    public function hasPermission(string|array $permissions): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return $this->roles()
            ->whereHas('permissions', function ($q) use ($permissions) {
                $q->whereIn('slug', $permissions);
            })
            ->exists();
    }
}
