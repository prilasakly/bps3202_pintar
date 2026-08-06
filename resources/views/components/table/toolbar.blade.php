{{--
    Toolbar generik buat tabel mana pun: kotak search + selector "tampilkan N baris".
    Diasumsikan dipakai DI DALAM elemen yang punya x-data dari pintarTableFactory /
    pintarClientTableFactory (lihat resources/views/layouts/app.blade.php), karena
    "search" & "perPage" di sini merujuk ke properti Alpine dari situ.

    Contoh pakai:
        <x-table.toolbar placeholder="Cari nama, email, atau NIP..." />

    Kalau butuh tombol tambahan di sisi kanan (misal "Tambah Data"), isi lewat slot:
        <x-table.toolbar placeholder="Cari...">
            <button ...>Tambah</button>
        </x-table.toolbar>
--}}
@props(['placeholder' => 'Cari...'])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="relative w-full sm:max-w-xs">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input type="text" x-model="search" placeholder="{{ $placeholder }}"
               class="w-full pl-9 pr-8 py-2 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
        <button type="button" x-show="search" x-cloak @click="search = ''"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="flex items-center gap-2 flex-wrap justify-end">
        {{ $slot ?? '' }}
        <label class="flex items-center gap-1.5 text-xs text-slate-500 shrink-0">
            Tampilkan
            <select x-model.number="perPage"
                    class="rounded-lg border border-slate-300 bg-white text-xs py-1.5 pl-2 pr-6 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                <template x-for="opt in perPageOptions" :key="opt">
                    <option :value="opt" x-text="opt"></option>
                </template>
            </select>
            baris
        </label>
    </div>
</div>
