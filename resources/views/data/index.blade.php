@php
    $sidebarOptions = $sidebars->map(fn ($s) => ['id' => (int) $s->id, 'nama' => $s->nama])->values();
@endphp

@extends('layouts.app')

@section('title', 'Kelola Data — PINTAR')
@section('eyebrow', 'Administrasi')
@section('page-title', 'Kelola Data')

@section('content')
<div x-data="dataManagement({{ Illuminate\Support\Js::from($sidebarOptions) }})">

    <p class="text-slate-500 text-sm max-w-2xl">
        Kelola kategori (subsidebar) dan indikator yang tampil di menu <strong>Data Makro</strong>.
        Semua user yang login bisa melihat daftar ini; tambah, ubah, dan hapus dibatasi hak akses
        <strong>Kelola Data</strong> (diatur lewat halaman Kelola Hak Akses -- default tim IPDS, Admin, dan Super Admin).
    </p>

    {{-- Guest: belum login sama sekali --}}
    <template x-if="$store.auth.isGuest">
        <div class="mt-6 p-5 rounded-xl bg-bps-orange-50 border border-bps-orange-200 text-bps-orange-800 text-sm flex items-center justify-between gap-3 flex-wrap">
            <span>Anda harus login untuk melihat halaman ini.</span>
            <button type="button" @click="$dispatch('open-login-modal')"
                    class="px-4 py-2 rounded-lg bg-bps-orange-500 text-white text-xs font-semibold hover:bg-bps-orange-600 transition shrink-0">
                Masuk Sekarang
            </button>
        </div>
    </template>

    <template x-if="$store.auth.isLoggedIn">
        <div class="mt-6 space-y-8">

            {{-- Notifikasi aksi (sukses/gagal) --}}
            <template x-if="pesan">
                <div class="p-4 rounded-xl text-sm border"
                     :class="{
                        'bg-bps-green-50 border-bps-green-200 text-bps-green-700': pesan.status === 'sukses',
                        'bg-rose-50 border-rose-200 text-rose-700': pesan.status === 'gagal',
                     }" x-text="pesan.pesan"></div>
            </template>

            {{-- ================= SEKSI KATEGORI (SUBSIDEBAR) ================= --}}
            <section>
                <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                    <h2 class="font-bold text-slate-800">Kategori (Subsidebar)</h2>
                    <button type="button" @click="openCreateSub()" x-show="$store.auth.can('data.manage')" x-cloak
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-bps-green-500 text-white text-xs font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Kategori
                    </button>
                </div>

                <div x-show="loadingSub" x-cloak class="p-8 text-center text-slate-400 text-sm">Memuat kategori...</div>
                <div x-show="errorSub" x-cloak class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm" x-text="errorSub"></div>

                <div x-show="!loadingSub && !errorSub" x-cloak class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-bps-green-600 text-white text-xs">
                                    <th class="px-4 py-3 text-left font-semibold">Menu Induk</th>
                                    <th class="px-4 py-3 text-left font-semibold">Nama Kategori</th>
                                    <th class="px-4 py-3 text-left font-semibold">Urutan</th>
                                    <th class="px-4 py-3 text-left font-semibold">Jumlah Indikator</th>
                                    <th class="px-4 py-3 text-right font-semibold" x-show="$store.auth.can('data.manage')" x-cloak>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="sub in subsidebars" :key="sub.id">
                                    <tr class="odd:bg-white even:bg-bps-green-50/40 hover:bg-bps-orange-50/60 transition-colors border-t border-slate-100">
                                        <td class="px-4 py-2.5 text-slate-600" x-text="sub.sidebar?.nama || '-'"></td>
                                        <td class="px-4 py-2.5 font-medium text-slate-700" x-text="sub.nama"></td>
                                        <td class="px-4 py-2.5 text-slate-600" x-text="sub.urutan"></td>
                                        <td class="px-4 py-2.5 text-slate-600" x-text="sub.indikators_count"></td>
                                        <td class="px-4 py-2.5 text-right" x-show="$store.auth.can('data.manage')" x-cloak>
                                            <div class="inline-flex items-center gap-1.5">
                                                <button type="button" @click="openEditSub(sub)"
                                                        class="p-1.5 rounded-lg text-bps-blue-600 hover:bg-bps-blue-50 transition" title="Ubah">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="removeSub(sub)" :disabled="deletingSubId === sub.id"
                                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition disabled:opacity-50" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="subsidebars.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada kategori.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- ================= SEKSI INDIKATOR ================= --}}
            <section x-data="{}" x-init="startIndikatorTable()">
                <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                    <h2 class="font-bold text-slate-800">Indikator</h2>
                    <button type="button" @click="openCreateInd()" x-show="$store.auth.can('data.manage')" x-cloak
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-bps-green-500 text-white text-xs font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Tambah Indikator
                    </button>
                </div>

                <x-table.toolbar placeholder="Cari nama indikator atau satuan..." />

                <div x-show="loading" x-cloak class="p-8 text-center text-slate-400 text-sm">Memuat daftar indikator...</div>
                <div x-show="error" x-cloak class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm" x-text="error"></div>

                <div x-show="!loading && !error" x-cloak class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="bg-bps-green-600 text-white text-xs">
                                    <x-table.sortable-th column="nama_judul">Nama Indikator</x-table.sortable-th>
                                    <th class="px-4 py-3 text-left font-semibold">Kategori</th>
                                    <x-table.sortable-th column="satuan">Satuan</x-table.sortable-th>
                                    <th class="px-4 py-3 text-left font-semibold">Jumlah Periode</th>
                                    <th class="px-4 py-3 text-right font-semibold" x-show="$store.auth.can('data.manage')" x-cloak>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="ind in items" :key="ind.id">
                                    <tr class="odd:bg-white even:bg-bps-green-50/40 hover:bg-bps-orange-50/60 transition-colors border-t border-slate-100">
                                        <td class="px-4 py-2.5 font-medium text-slate-700" x-text="ind.nama_judul"></td>
                                        <td class="px-4 py-2.5 text-slate-600">
                                            <span x-text="ind.subsidebar?.nama || '-'"></span>
                                            <span class="text-slate-400" x-show="ind.subsidebar?.sidebar" x-text="' (' + ind.subsidebar?.sidebar + ')'"></span>
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-600" x-text="ind.satuan || '-'"></td>
                                        <td class="px-4 py-2.5 text-slate-600" x-text="ind.periode_count"></td>
                                        <td class="px-4 py-2.5 text-right" x-show="$store.auth.can('data.manage')" x-cloak>
                                            <div class="inline-flex items-center gap-1.5">
                                                <a :href="'/indikator/' + ind.slug" target="_blank"
                                                   class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 transition inline-flex" title="Lihat halaman publik">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                    </svg>
                                                </a>
                                                <button type="button" @click="openEditInd(ind)"
                                                        class="p-1.5 rounded-lg text-bps-blue-600 hover:bg-bps-blue-50 transition" title="Ubah">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="removeInd(ind)" :disabled="deletingIndId === ind.id"
                                                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition disabled:opacity-50" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                        <span x-text="search ? 'Tidak ada indikator yang cocok dengan pencarian.' : 'Belum ada indikator.'"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <x-table.pagination />
                </div>
            </section>
        </div>
    </template>

    {{-- Modal tambah/ubah kategori --}}
    <div x-show="showSubModal && $store.auth.can('data.manage')" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-slate-900/50" @click="closeSubModal()"></div>

        <div class="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800" x-text="editingSubId ? 'Ubah Kategori' : 'Tambah Kategori'"></h2>
                <button type="button" @click="closeSubModal()" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitSubForm()" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Menu Induk</label>
                    <select x-model.number="subForm.sidebar_id" required
                            class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                        <option value="" disabled>-- Pilih menu induk --</option>
                        <template x-for="s in sidebarOptions" :key="s.id">
                            <option :value="s.id" x-text="s.nama"></option>
                        </template>
                    </select>
                    <template x-if="subErrors.sidebar_id"><p class="mt-1 text-xs text-rose-500" x-text="subErrors.sidebar_id[0]"></p></template>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Kategori</label>
                    <input type="text" x-model="subForm.nama" required placeholder="cth: KEPENDUDUKAN DAN MIGRASI"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    <template x-if="subErrors.nama"><p class="mt-1 text-xs text-rose-500" x-text="subErrors.nama[0]"></p></template>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Urutan Tampil</label>
                    <input type="number" min="0" x-model.number="subForm.urutan" placeholder="Otomatis kalau dikosongkan"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="closeSubModal()"
                            class="px-4 py-2 rounded-lg text-slate-600 text-sm font-semibold hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="submittingSub"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm disabled:opacity-60">
                        <span x-show="!submittingSub" x-text="editingSubId ? 'Simpan Perubahan' : 'Tambah Kategori'"></span>
                        <span x-show="submittingSub" x-cloak>Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal tambah/ubah indikator --}}
    <div x-show="showIndModal && $store.auth.can('data.manage')" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-slate-900/50" @click="closeIndModal()"></div>

        <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h2 class="font-bold text-slate-800" x-text="editingIndId ? 'Ubah Indikator' : 'Tambah Indikator'"></h2>
                <button type="button" @click="closeIndModal()" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitIndForm()" class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                    <select x-model.number="indForm.subsidebar_id" required
                            class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                        <option value="" disabled>-- Pilih kategori --</option>
                        <template x-for="s in subsidebars" :key="s.id">
                            <option :value="s.id" x-text="(s.sidebar?.nama ? s.sidebar.nama + ' — ' : '') + s.nama"></option>
                        </template>
                    </select>
                    <template x-if="indErrors.subsidebar_id"><p class="mt-1 text-xs text-rose-500" x-text="indErrors.subsidebar_id[0]"></p></template>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama / Judul Indikator</label>
                    <input type="text" x-model="indForm.nama_judul" required placeholder="cth: Jumlah Guru MA Menurut Kecamatan"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    <template x-if="indErrors.nama_judul"><p class="mt-1 text-xs text-rose-500" x-text="indErrors.nama_judul[0]"></p></template>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Satuan</label>
                        <input type="text" x-model="indForm.satuan" placeholder="cth: orang, ha, km"
                               class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tipe Baris</label>
                        <select x-model="indForm.tipe_baris"
                                class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                            <option value="kecamatan">Kecamatan</option>
                            <option value="kelompok_umur">Kelompok Umur</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Urutan Tampil</label>
                    <input type="number" min="0" x-model.number="indForm.urutan" placeholder="Otomatis kalau dikosongkan"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                </div>

                <p class="text-xs text-slate-400">
                    Data nilai per tahun/triwulan diisi lewat menu <a href="{{ route('upload.create') }}" class="text-bps-green-700 font-semibold hover:underline">Upload Data</a>, setelah indikatornya dibuat di sini.
                </p>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="closeIndModal()"
                            class="px-4 py-2 rounded-lg text-slate-600 text-sm font-semibold hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="submittingInd"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm disabled:opacity-60">
                        <span x-show="!submittingInd" x-text="editingIndId ? 'Simpan Perubahan' : 'Tambah Indikator'"></span>
                        <span x-show="submittingInd" x-cloak>Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dataManagement', (sidebarOptions) => ({
        // Tabel indikator (search + sort + pagination) dipasok mixin generik pintarTableFactory
        // (lihat resources/views/layouts/app.blade.php), disambungkan ke endpoint /api/indikators.
        ...window.pintarTableFactory({
            endpoint: '/api/indikators',
            defaultSort: 'nama_judul',
            defaultDir: 'asc',
            perPage: 10,
            errorMessage: 'Tidak bisa memuat daftar indikator. Coba muat ulang halaman.',
        }),
        _indTableStarted: false,

        sidebarOptions: sidebarOptions,
        pesan: null,

        // ===== Kategori (Subsidebar) -- daftar kecil, cukup fetch sekali tanpa pagination server =====
        subsidebars: [],
        loadingSub: false,
        errorSub: '',
        showSubModal: false,
        editingSubId: null,
        submittingSub: false,
        subErrors: {},
        subForm: { sidebar_id: '', nama: '', urutan: null },
        deletingSubId: null,

        // ===== Indikator (modal tambah/ubah) =====
        showIndModal: false,
        editingIndId: null,
        _editingIndSlug: null,
        submittingInd: false,
        indErrors: {},
        indForm: { subsidebar_id: '', nama_judul: '', satuan: '', tipe_baris: 'kecamatan', urutan: null },
        deletingIndId: null,

        init() {
            if (this.$store.auth.isLoggedIn) this.fetchSubsidebars();
            this.$watch('$store.auth.isLoggedIn', (loggedIn) => {
                if (loggedIn) this.fetchSubsidebars();
            });
        },

        // initTable() (dari mixin) mendaftarkan watcher search/perPage lalu langsung fetch
        // pertama kali -- dipanggil manual di sini supaya nunggu user login dulu.
        startIndikatorTable() {
            if (this._indTableStarted) return;
            if (this.$store.auth.isLoggedIn) {
                this._indTableStarted = true;
                this.initTable();
            } else {
                this.$watch('$store.auth.isLoggedIn', (loggedIn) => {
                    if (loggedIn && !this._indTableStarted) {
                        this._indTableStarted = true;
                        this.initTable();
                    }
                });
            }
        },

        async fetchSubsidebars() {
            this.loadingSub = true;
            this.errorSub = '';
            try {
                const res = await fetch('/api/subsidebars', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('Gagal memuat kategori.');
                this.subsidebars = await res.json();
            } catch (e) {
                this.errorSub = 'Tidak bisa memuat daftar kategori. Coba muat ulang halaman.';
            } finally {
                this.loadingSub = false;
            }
        },

        // ---------- Kategori: create/edit/delete ----------
        openCreateSub() {
            this.subForm = { sidebar_id: this.sidebarOptions[0]?.id ?? '', nama: '', urutan: null };
            this.subErrors = {};
            this.editingSubId = null;
            this.showSubModal = true;
        },

        openEditSub(sub) {
            this.editingSubId = sub.id;
            this.subErrors = {};
            this.subForm = { sidebar_id: sub.sidebar_id, nama: sub.nama, urutan: sub.urutan };
            this.showSubModal = true;
        },

        closeSubModal() {
            this.showSubModal = false;
        },

        async submitSubForm() {
            this.submittingSub = true;
            this.subErrors = {};

            const isEdit = this.editingSubId !== null;
            const url = isEdit ? '/api/subsidebars/' + this.editingSubId : '/api/subsidebars';

            try {
                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.subForm),
                });
                const data = await res.json();

                if (res.status === 422 && data.errors) {
                    this.subErrors = data.errors;
                    return;
                }
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Terjadi kesalahan.' };
                    return;
                }

                this.pesan = { status: 'sukses', pesan: data.message };
                this.showSubModal = false;
                await this.fetchSubsidebars();
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                this.submittingSub = false;
            }
        },

        async removeSub(sub) {
            if (!confirm(`Hapus kategori "${sub.nama}"? Kategori yang masih punya indikator tidak bisa dihapus.`)) return;
            this.deletingSubId = sub.id;
            try {
                const res = await fetch('/api/subsidebars/' + sub.id, {
                    method: 'DELETE',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Gagal menghapus kategori.' };
                    return;
                }
                this.pesan = { status: 'sukses', pesan: data.message };
                await this.fetchSubsidebars();
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                this.deletingSubId = null;
            }
        },

        // ---------- Indikator: create/edit/delete ----------
        openCreateInd() {
            this.indForm = { subsidebar_id: this.subsidebars[0]?.id ?? '', nama_judul: '', satuan: '', tipe_baris: 'kecamatan', urutan: null };
            this.indErrors = {};
            this.editingIndId = null;
            this.showIndModal = true;
        },

        openEditInd(ind) {
            this.editingIndId = ind.id;
            this._editingIndSlug = ind.slug;
            this.indErrors = {};
            this.indForm = {
                subsidebar_id: ind.subsidebar_id ?? this.subsidebars.find((s) => s.nama === ind.subsidebar?.nama)?.id ?? '',
                nama_judul: ind.nama_judul,
                satuan: ind.satuan || '',
                tipe_baris: ind.tipe_baris || 'kecamatan',
                urutan: ind.urutan,
            };
            this.showIndModal = true;
        },

        closeIndModal() {
            this.showIndModal = false;
        },

        async submitIndForm() {
            this.submittingInd = true;
            this.indErrors = {};

            const isEdit = this.editingIndId !== null;
            const url = isEdit ? ('/api/indikators/' + this._editingIndSlug) : '/api/indikators';

            try {
                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.indForm),
                });
                const data = await res.json();

                if (res.status === 422 && data.errors) {
                    this.indErrors = data.errors;
                    return;
                }
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Terjadi kesalahan.' };
                    return;
                }

                this.pesan = { status: 'sukses', pesan: data.message };
                this.showIndModal = false;
                await this.fetchData();
                await this.fetchSubsidebars();
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                this.submittingInd = false;
            }
        },

        async removeInd(ind) {
            if (!confirm(`Hapus indikator "${ind.nama_judul}" beserta seluruh datanya? Tindakan ini tidak bisa dibatalkan.`)) return;
            this.deletingIndId = ind.id;
            try {
                const res = await fetch('/api/indikators/' + ind.slug, {
                    method: 'DELETE',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok) {
                    this.pesan = { status: 'gagal', pesan: data.message || 'Gagal menghapus indikator.' };
                    return;
                }
                this.pesan = { status: 'sukses', pesan: data.message };
                await this.fetchData();
                await this.fetchSubsidebars();
            } catch (e) {
                this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
            } finally {
                this.deletingIndId = null;
            }
        },
    }));
});
</script>
@endsection
