{{--
    Footer pagination generik. Sama seperti x-table.toolbar, dipakai DI DALAM elemen
    yang punya x-data dari pintarTableFactory / pintarClientTableFactory.

    Contoh pakai:
        <x-table.pagination />
--}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3.5 border-t border-slate-100 bg-slate-50/60 text-xs text-slate-500">
    <p>
        <template x-if="total > 0">
            <span>
                Menampilkan <span class="font-semibold text-slate-700" x-text="from"></span>–<span class="font-semibold text-slate-700" x-text="to"></span>
                dari <span class="font-semibold text-slate-700" x-text="total"></span> data
            </span>
        </template>
        <template x-if="total === 0">
            <span>Tidak ada data yang cocok.</span>
        </template>
    </p>

    <div class="inline-flex items-center gap-1" x-show="lastPage > 1" x-cloak>
        <button type="button" @click="goToPage(1)" :disabled="page === 1"
                class="px-2 py-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-white transition" title="Halaman pertama">«</button>
        <button type="button" @click="goToPage(page - 1)" :disabled="page === 1"
                class="px-2 py-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-white transition" title="Sebelumnya">‹</button>

        <template x-for="(p, idx) in pageNumbers" :key="idx">
            <button type="button" @click="p !== '...' && goToPage(p)" :disabled="p === '...'"
                    :class="p === page ? 'bg-bps-green-500 text-white border-bps-green-500' : 'border-slate-200 hover:bg-white text-slate-600'"
                    class="min-w-[2rem] px-2 py-1.5 rounded-lg border text-xs font-medium transition" x-text="p"></button>
        </template>

        <button type="button" @click="goToPage(page + 1)" :disabled="page === lastPage"
                class="px-2 py-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-white transition" title="Berikutnya">›</button>
        <button type="button" @click="goToPage(lastPage)" :disabled="page === lastPage"
                class="px-2 py-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-white transition" title="Halaman terakhir">»</button>
    </div>
</div>
