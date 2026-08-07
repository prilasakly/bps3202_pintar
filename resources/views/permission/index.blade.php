@extends('layouts.app')

@section('title', 'Kelola Hak Akses — PINTAR')
@section('eyebrow', 'Administrasi')
@section('page-title', 'Kelola Hak Akses')

@section('content')
<div x-data="accessManagement()">

    <p class="text-slate-500 text-sm max-w-2xl">
        Atur role/tim mana saja yang boleh melakukan setiap aksi berikut. Perubahan di sini
        langsung berlaku -- tidak perlu ubah kode atau deploy ulang. Halaman ini khusus
        <strong>Super Admin</strong>.
    </p>

    {{-- Guest --}}
    <template x-if="$store.auth.isGuest">
        <div class="mt-6 p-5 rounded-xl bg-bps-orange-50 border border-bps-orange-200 text-bps-orange-800 text-sm flex items-center justify-between gap-3 flex-wrap">
            <span>Anda harus login sebagai Super Admin untuk melihat halaman ini.</span>
            <button type="button" @click="$dispatch('open-login-modal')"
                    class="px-4 py-2 rounded-lg bg-bps-orange-500 text-white text-xs font-semibold hover:bg-bps-orange-600 transition shrink-0">
                Masuk Sekarang
            </button>
        </div>
    </template>

    {{-- Login tapi bukan superadmin --}}
    <template x-if="$store.auth.isLoggedIn && !$store.auth.isSuperadmin">
        <div class="mt-6 p-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            Akun Anda (<span x-text="$store.auth.roles.join(', ') || '-'"></span>) tidak memiliki akses ke halaman ini.
            Hanya <strong>Super Admin</strong> yang bisa mengatur hak akses.
        </div>
    </template>

    <template x-if="$store.auth.isSuperadmin">
        <div class="mt-6">
            {{-- Notifikasi aksi --}}
            <template x-if="pesan">
                <div class="mb-5 p-4 rounded-xl text-sm border"
                     :class="{
                        'bg-bps-green-50 border-bps-green-200 text-bps-green-700': pesan.status === 'sukses',
                        'bg-rose-50 border-rose-200 text-rose-700': pesan.status === 'gagal',
                     }" x-text="pesan.pesan"></div>
            </template>

            <div x-show="loading" x-cloak class="p-8 text-center text-slate-400 text-sm">Memuat data hak akses...</div>
            <div x-show="error" x-cloak class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm" x-text="error"></div>

            <div x-show="!loading && !error" x-cloak class="space-y-6">
                <template x-for="[grup, list] in Object.entries(permissionsByGroup)" :key="grup">
                    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                            <h2 class="font-bold text-slate-700 text-sm" x-text="grup"></h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <template x-for="perm in list" :key="perm.id">
                                <div class="p-5 flex flex-col sm:flex-row sm:items-start gap-4">
                                    <div class="sm:w-64 shrink-0">
                                        <p class="font-semibold text-slate-800 text-sm" x-text="perm.nama"></p>
                                        <p class="text-xs text-slate-400 mt-0.5" x-text="perm.deskripsi"></p>
                                        <p class="text-[10px] text-slate-300 mt-1 font-mono" x-text="perm.slug"></p>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="role in roles" :key="role.id">
                                                <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition"
                                                       :class="perm._selected.includes(role.id) ? 'bg-bps-green-50 border-bps-green-400 text-bps-green-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                                    <input type="checkbox" class="rounded border-slate-300 text-bps-green-600 focus:ring-bps-green-500"
                                                           :checked="perm._selected.includes(role.id)"
                                                           :disabled="role.slug === 'superadmin'"
                                                           @change="toggleRoleFor(perm, role.id)">
                                                    <span x-text="role.nama"></span>
                                                </label>
                                            </template>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-2">
                                            Super Admin selalu punya semua hak akses dan tidak bisa dilepas dari sini.
                                        </p>
                                    </div>
                                    <div class="shrink-0 flex items-center">
                                        <button type="button" @click="saveGrant(perm)" :disabled="perm._saving || !perm._dirty"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-bps-green-500 text-white text-xs font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm disabled:opacity-50">
                                            <span x-show="!perm._saving" x-text="perm._dirty ? 'Simpan' : 'Tersimpan'"></span>
                                            <span x-show="perm._saving" x-cloak>Menyimpan...</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="permissions.length === 0" class="p-8 text-center text-slate-400 text-sm bg-white border border-slate-200 rounded-2xl">
                    Belum ada permission yang terdaftar.
                </div>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('accessManagement', () => ({
        permissions: [],
        roles: [],
        loading: false,
        error: '',
        pesan: null,

        init() {
            if (this.$store.auth.isSuperadmin) this.fetchData();
            this.$watch('$store.auth.isSuperadmin', (isSuperadmin) => {
                if (isSuperadmin) this.fetchData();
            });
        },

        get permissionsByGroup() {
            const grouped = {};
            for (const perm of this.permissions) {
                if (!grouped[perm.grup]) grouped[perm.grup] = [];
                grouped[perm.grup].push(perm);
            }
            return grouped;
        },

        async fetchData() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/api/permissions', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('Gagal memuat data hak akses.');
                const data = await res.json();

                this.roles = data.roles;
                const superadminId = this.roles.find((r) => r.slug === 'superadmin')?.id;
                this.permissions = data.permissions.map((p) => {
                    const selected = superadminId && !p.role_ids.includes(superadminId)
                        ? [...p.role_ids, superadminId]
                        : [...p.role_ids];
                    return {
                        ...p,
                        // "_selected" adalah state checkbox yang sedang diedit di form. "_baseline"
                        // adalah acuan pembanding untuk status dirty (SUDAH termasuk superadmin yang
                        // ditambahkan otomatis secara visual, supaya tidak dianggap "berubah" padahal
                        // user belum menyentuh apa-apa). "_dirty" menandakan ada perubahan yang
                        // belum disimpan.
                        _selected: selected,
                        _baseline: [...selected],
                        _dirty: false,
                        _saving: false,
                    };
                });
            } catch (e) {
                this.error = 'Tidak bisa memuat data hak akses. Coba muat ulang halaman.';
            } finally {
                this.loading = false;
            }
        },

        toggleRoleFor(perm, roleId) {
            const idx = perm._selected.indexOf(roleId);
            if (idx === -1) perm._selected.push(roleId);
            else perm._selected.splice(idx, 1);
            perm._dirty = JSON.stringify([...perm._selected].sort()) !== JSON.stringify([...perm._baseline].sort());
        },

        async saveGrant(perm) {
            perm._saving = true;
            try {
                const res = await fetch('/api/permissions/' + perm.id, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ role_ids: perm._selected }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Gagal menyimpan hak akses.' };
                    return;
                }
                perm.role_ids = [...perm._selected];
                perm._baseline = [...perm._selected];
                perm._dirty = false;
                this.pesan = { status: 'sukses', pesan: data.message };
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                perm._saving = false;
            }
        },
    }));
});
</script>
@endsection
