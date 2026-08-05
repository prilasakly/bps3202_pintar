@php
    $roleOptions = $roles->map(fn ($r) => ['id' => (int) $r->id, 'nama' => $r->nama, 'slug' => $r->slug])->values();
@endphp

@extends('layouts.app')

@section('title', 'Kelola User — PINTAR')
@section('eyebrow', 'Administrasi')
@section('page-title', 'Kelola User')

@section('content')
<div x-data="userManagement({{ Illuminate\Support\Js::from($roleOptions) }})">

    <p class="text-slate-500 text-sm max-w-2xl">
        Daftar akun user PINTAR beserta data kepegawaian dan role/tim masing-masing.
        Semua user yang login bisa melihat daftar ini; tambah, ubah, dan hapus user
        hanya bisa dilakukan oleh role <strong>Super Admin</strong>.
    </p>

    {{-- Guest: belum login sama sekali --}}
    <template x-if="$store.auth.isGuest">
        <div class="mt-6 p-5 rounded-xl bg-bps-orange-50 border border-bps-orange-200 text-bps-orange-800 text-sm flex items-center justify-between gap-3 flex-wrap">
            <span>Anda harus login untuk melihat daftar user.</span>
            <button type="button" @click="$dispatch('open-login-modal')"
                    class="px-4 py-2 rounded-lg bg-bps-orange-500 text-white text-xs font-semibold hover:bg-bps-orange-600 transition shrink-0">
                Masuk Sekarang
            </button>
        </div>
    </template>

    <template x-if="$store.auth.isLoggedIn">
        <div class="mt-6">

            {{-- Notifikasi aksi (sukses/gagal) --}}
            <template x-if="pesan">
                <div class="mb-5 p-4 rounded-xl text-sm border"
                     :class="{
                        'bg-bps-green-50 border-bps-green-200 text-bps-green-700': pesan.status === 'sukses',
                        'bg-rose-50 border-rose-200 text-rose-700': pesan.status === 'gagal',
                     }" x-text="pesan.pesan"></div>
            </template>

            {{-- Tombol tambah/import user: khusus superadmin --}}
            <div class="flex items-center justify-between gap-3 mb-4 flex-wrap" x-show="$store.auth.isSuperadmin" x-cloak>
                <span class="text-xs text-slate-400" x-text="users.length + ' user terdaftar'"></span>
                <div class="inline-flex items-center gap-2 flex-wrap">
                    <button type="button" @click="downloadTemplate()" :disabled="downloadingTemplate"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white border border-slate-300 text-slate-600 text-xs font-semibold hover:bg-slate-50 active:scale-[0.98] transition disabled:opacity-60">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span x-text="downloadingTemplate ? 'Mengunduh...' : 'Download Template'"></span>
                    </button>
                    <button type="button" @click="openImport()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-bps-blue-500 text-white text-xs font-semibold hover:bg-bps-blue-600 active:scale-[0.98] transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        Import Excel
                    </button>
                    <button type="button" @click="openCreate()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-bps-green-500 text-white text-xs font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah User
                    </button>
                </div>
            </div>

            {{-- Loading state --}}
            <div x-show="loading" x-cloak class="p-8 text-center text-slate-400 text-sm">Memuat daftar user...</div>

            {{-- Load error --}}
            <div x-show="loadError" x-cloak class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm" x-text="loadError"></div>

            {{-- Tabel user --}}
            <div x-show="!loading && !loadError" x-cloak class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-bps-green-600 text-white text-xs">
                                <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold">Email</th>
                                <th class="px-4 py-3 text-left font-semibold">NIP Lama</th>
                                <th class="px-4 py-3 text-left font-semibold">NIP Baru</th>
                                <th class="px-4 py-3 text-left font-semibold">Golongan</th>
                                <th class="px-4 py-3 text-left font-semibold">Jabatan</th>
                                <th class="px-4 py-3 text-left font-semibold">Role</th>
                                <th class="px-4 py-3 text-right font-semibold" x-show="$store.auth.isSuperadmin" x-cloak>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="user in users" :key="user.id">
                                <tr class="odd:bg-white even:bg-bps-green-50/40 hover:bg-bps-orange-50/60 transition-colors border-t border-slate-100">
                                    <td class="px-4 py-2.5 font-medium text-slate-700" x-text="user.name"></td>
                                    <td class="px-4 py-2.5 text-slate-600" x-text="user.email"></td>
                                    <td class="px-4 py-2.5 text-slate-600" x-text="user.nip_lama || '-'"></td>
                                    <td class="px-4 py-2.5 text-slate-600" x-text="user.nip_baru || '-'"></td>
                                    <td class="px-4 py-2.5 text-slate-600" x-text="user.golongan || '-'"></td>
                                    <td class="px-4 py-2.5 text-slate-600" x-text="user.jabatan || '-'"></td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex flex-wrap gap-1">
                                            <template x-for="role in user.roles" :key="role.id">
                                                <span class="px-2 py-0.5 rounded-full bg-bps-blue-50 text-bps-blue-700 text-[10px] font-semibold" x-text="role.nama"></span>
                                            </template>
                                            <span x-show="user.roles.length === 0" class="text-[10px] text-slate-400 italic">Belum ada role</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-right" x-show="$store.auth.isSuperadmin" x-cloak>
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" @click="openEdit(user)"
                                                    class="p-1.5 rounded-lg text-bps-blue-600 hover:bg-bps-blue-50 transition" title="Ubah">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="removeUser(user)" :disabled="deletingId === user.id"
                                                    class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition disabled:opacity-50" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="users.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400">Belum ada user.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal tambah/ubah user --}}
    <div x-show="showModal && $store.auth.isSuperadmin" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-slate-900/50" @click="closeModal()"></div>

        <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800" x-text="editingId ? 'Ubah User' : 'Tambah User'"></h2>
                <button type="button" @click="closeModal()" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitForm()" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama</label>
                    <input type="text" x-model="form.name" required
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    <template x-if="errors.name"><p class="mt-1 text-xs text-rose-500" x-text="errors.name[0]"></p></template>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" x-model="form.email" required
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    <template x-if="errors.email"><p class="mt-1 text-xs text-rose-500" x-text="errors.email[0]"></p></template>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Kata Sandi
                        <span class="text-slate-400 font-normal" x-show="editingId" x-cloak>(kosongkan jika tidak diganti)</span>
                    </label>
                    <input type="password" x-model="form.password" :required="!editingId" autocomplete="new-password"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    <template x-if="errors.password"><p class="mt-1 text-xs text-rose-500" x-text="errors.password[0]"></p></template>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">NIP Lama</label>
                        <input type="text" x-model="form.nip_lama"
                               class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">NIP Baru</label>
                        <input type="text" x-model="form.nip_baru"
                               class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Golongan</label>
                        <input type="text" x-model="form.golongan" placeholder="cth: III/c"
                               class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Jabatan</label>
                        <input type="text" x-model="form.jabatan"
                               class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Role / Tim</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="role in roleOptions" :key="role.id">
                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition"
                                   :class="form.roles.includes(role.id) ? 'bg-bps-green-50 border-bps-green-400 text-bps-green-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                <input type="checkbox" class="rounded border-slate-300 text-bps-green-600 focus:ring-bps-green-500"
                                       :checked="form.roles.includes(role.id)" @change="toggleRole(role.id)">
                                <span x-text="role.nama"></span>
                            </label>
                        </template>
                    </div>
                    <template x-if="errors.roles"><p class="mt-1 text-xs text-rose-500" x-text="errors.roles[0]"></p></template>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 rounded-lg text-slate-600 text-sm font-semibold hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm disabled:opacity-60">
                        <span x-show="!submitting" x-text="editingId ? 'Simpan Perubahan' : 'Tambah User'"></span>
                        <span x-show="submitting" x-cloak>Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal import user via Excel: khusus superadmin --}}
    <div x-show="showImportModal && $store.auth.isSuperadmin" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-slate-900/50" @click="!importing && closeImportModal()"></div>

        <div class="relative z-10 w-full max-w-xl bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Import User dari Excel</h2>
                <button type="button" @click="!importing && closeImportModal()" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                {{-- Belum ada hasil import: form upload --}}
                <template x-if="!importResult">
                    <div class="space-y-4">
                        <div class="p-3.5 rounded-xl bg-bps-blue-50 border border-bps-blue-100 text-bps-blue-800 text-xs leading-relaxed">
                            Gunakan file Excel sesuai format template resmi. Belum punya templatenya?
                            <button type="button" @click="downloadTemplate()" class="font-semibold underline hover:no-underline">Download Template</button>
                            dulu, isi datanya mulai baris ke-2, lalu upload di sini. Baris yang gagal tidak akan menggagalkan baris lain.
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">File Excel (.xls / .xlsx, maks 10MB)</label>
                            <input type="file" accept=".xls,.xlsx" @change="onImportFileChange($event)"
                                   class="w-full text-sm text-slate-600 file:mr-3 file:px-3.5 file:py-2 file:rounded-lg file:border-0 file:bg-bps-green-50 file:text-bps-green-700 file:text-xs file:font-semibold hover:file:bg-bps-green-100 border border-slate-300 rounded-lg cursor-pointer">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="closeImportModal()" :disabled="importing"
                                    class="px-4 py-2 rounded-lg text-slate-600 text-sm font-semibold hover:bg-slate-100 transition disabled:opacity-60">
                                Batal
                            </button>
                            <button type="button" @click="submitImport()" :disabled="!importFile || importing"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-blue-500 text-white text-sm font-semibold hover:bg-bps-blue-600 active:scale-[0.98] transition shadow-sm disabled:opacity-60">
                                <span x-show="!importing" x-text="'Import'"></span>
                                <span x-show="importing" x-cloak>Memproses...</span>
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Sudah ada hasil import: tampilkan laporan per baris --}}
                <template x-if="importResult">
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl text-sm border"
                             :class="{
                                'bg-bps-green-50 border-bps-green-200 text-bps-green-700': importResult.status === 'sukses',
                                'bg-bps-orange-50 border-bps-orange-200 text-bps-orange-800': importResult.status === 'sebagian',
                                'bg-rose-50 border-rose-200 text-rose-700': importResult.status === 'gagal',
                             }" x-text="importResult.pesan"></div>

                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                                <div class="font-bold text-slate-700 text-base" x-text="importResult.jumlah_baris"></div>
                                <div class="text-slate-400">Total Baris</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-bps-green-50 border border-bps-green-200">
                                <div class="font-bold text-bps-green-700 text-base" x-text="importResult.jumlah_berhasil"></div>
                                <div class="text-bps-green-600">Berhasil</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-rose-50 border border-rose-200">
                                <div class="font-bold text-rose-700 text-base" x-text="importResult.jumlah_gagal"></div>
                                <div class="text-rose-600">Gagal</div>
                            </div>
                        </div>

                        <template x-if="importResult.errors && importResult.errors.length">
                            <div>
                                <p class="text-xs font-semibold text-slate-600 mb-1.5">Baris gagal — perbaiki lalu upload ulang:</p>
                                <div class="max-h-40 overflow-y-auto custom-scrollbar rounded-lg border border-rose-200">
                                    <template x-for="err in importResult.errors" :key="err.baris">
                                        <div class="px-3 py-2 text-xs border-b border-rose-100 last:border-b-0 bg-rose-50/60">
                                            <span class="font-semibold text-rose-700" x-text="'Baris ' + err.baris + ':'"></span>
                                            <span class="text-rose-600" x-text="' ' + err.pesan"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="importResult.berhasil && importResult.berhasil.length">
                            <div>
                                <p class="text-xs font-semibold text-slate-600 mb-1.5">Baris berhasil:</p>
                                <div class="max-h-40 overflow-y-auto custom-scrollbar rounded-lg border border-bps-green-200">
                                    <template x-for="ok in importResult.berhasil" :key="ok.baris">
                                        <div class="px-3 py-2 text-xs border-b border-bps-green-100 last:border-b-0 bg-bps-green-50/60 flex items-center justify-between gap-2">
                                            <span class="text-slate-600">
                                                <span class="font-semibold text-slate-700" x-text="'Baris ' + ok.baris + ':'"></span>
                                                <span x-text="' ' + ok.nama + ' (' + ok.email + ')'"></span>
                                            </span>
                                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-bps-green-100 text-bps-green-700 text-[10px] font-semibold" x-text="ok.aksi"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" x-show="importResult.jumlah_gagal > 0" @click="importResult = null; importFile = null"
                                    class="px-4 py-2 rounded-lg text-slate-600 text-sm font-semibold hover:bg-slate-100 transition">
                                Upload File Lain
                            </button>
                            <button type="button" @click="closeImportModal()"
                                    class="px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm">
                                Selesai
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userManagement', (roleOptions) => ({
        roleOptions: roleOptions,
        users: [],
        loading: false,
        loadError: '',
        loaded: false,

        showModal: false,
        editingId: null,
        submitting: false,
        errors: {},
        form: { name: '', email: '', password: '', nip_lama: '', nip_baru: '', golongan: '', jabatan: '', roles: [] },

        deletingId: null,
        pesan: null,

        showImportModal: false,
        importFile: null,
        importing: false,
        importResult: null,
        downloadingTemplate: false,

        init() {
            if (this.$store.auth.isLoggedIn) this.loadUsers();
            this.$watch('$store.auth.isLoggedIn', (loggedIn) => {
                if (loggedIn && !this.loaded) this.loadUsers();
            });
        },

        async loadUsers() {
            this.loading = true;
            this.loadError = '';
            try {
                const res = await fetch('/api/users', {
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) throw new Error('Gagal memuat daftar user.');
                this.users = await res.json();
                this.loaded = true;
            } catch (e) {
                this.loadError = 'Tidak bisa memuat daftar user. Coba muat ulang halaman.';
            } finally {
                this.loading = false;
            }
        },

        resetForm() {
            this.form = { name: '', email: '', password: '', nip_lama: '', nip_baru: '', golongan: '', jabatan: '', roles: [] };
            this.errors = {};
        },

        openCreate() {
            this.resetForm();
            this.editingId = null;
            this.showModal = true;
        },

        openEdit(user) {
            this.editingId = user.id;
            this.errors = {};
            this.form = {
                name: user.name,
                email: user.email,
                password: '',
                nip_lama: user.nip_lama || '',
                nip_baru: user.nip_baru || '',
                golongan: user.golongan || '',
                jabatan: user.jabatan || '',
                roles: user.roles.map(r => r.id),
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        toggleRole(id) {
            const idx = this.form.roles.indexOf(id);
            if (idx === -1) this.form.roles.push(id);
            else this.form.roles.splice(idx, 1);
        },

        async submitForm() {
            this.submitting = true;
            this.errors = {};

            const isEdit = this.editingId !== null;
            const url = isEdit ? '/api/users/' + this.editingId : '/api/users';
            const payload = { ...this.form };
            
            if (isEdit && !payload.password) delete payload.password;

            try {
                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.status === 422 && data.errors) {
                    this.errors = data.errors;
                    return;
                }
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Terjadi kesalahan.' };
                    return;
                }

                this.pesan = { status: 'sukses', pesan: data.message };
                this.showModal = false;
                await this.loadUsers();
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                this.submitting = false;
            }
        },

        async removeUser(user) {
            if (!confirm(`Hapus user "${user.name}"? Tindakan ini tidak bisa dibatalkan.`)) return;
            this.deletingId = user.id;
            try {
                const res = await fetch('/api/users/' + user.id, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Gagal menghapus user.' };
                    return;
                }
                this.pesan = { status: 'sukses', pesan: data.message };
                await this.loadUsers();
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                this.deletingId = null;
            }
        },

        openImport() {
            this.importFile = null;
            this.importResult = null;
            this.showImportModal = true;
        },

        closeImportModal() {
            this.showImportModal = false;
        },

        onImportFileChange(event) {
            this.importFile = event.target.files[0] || null;
        },

        async submitImport() {
            if (!this.importFile) return;

            this.importing = true;
            this.importResult = null;

            const formData = new FormData();
            formData.append('file', this.importFile);

            try {
                const res = await fetch('/api/users/import', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        // Sengaja tanpa Content-Type: browser yang set multipart/form-data + boundary-nya.
                    },
                    body: formData,
                });
                const data = await res.json();
                this.importResult = data;

                if (data.jumlah_berhasil > 0) {
                    await this.loadUsers();
                }
            } catch (e) {
                this.importResult = {
                    status: 'gagal',
                    pesan: 'Tidak bisa menghubungi server. Coba lagi.',
                    jumlah_baris: 0,
                    jumlah_berhasil: 0,
                    jumlah_gagal: 0,
                    berhasil: [],
                    errors: [],
                };
            } finally {
                this.importing = false;
            }
        },

        async downloadTemplate() {
            this.downloadingTemplate = true;
            try {
                const res = await fetch('/api/users/template', {
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                    },
                });
                if (!res.ok) throw new Error('Gagal mengunduh template.');

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'template-import-user-pintar.xlsx';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Gagal mengunduh template. Coba lagi.' };
            } finally {
                this.downloadingTemplate = false;
            }
        },
    }));
});
</script>
@endsection