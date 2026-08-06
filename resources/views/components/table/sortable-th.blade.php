{{--
    <th> yang bisa diklik buat sort, lengkap dengan indikator panah arah sort-nya.
    Dipakai DI DALAM elemen yang punya x-data dari pintarTableFactory / pintarClientTableFactory.

    Contoh pakai:
        <x-table.sortable-th column="name">Nama</x-table.sortable-th>
        <x-table.sortable-th column="nilai" align="right">Nilai</x-table.sortable-th>

    Atribut HTML tambahan (rowspan, colspan, class custom, dst) otomatis diteruskan ke <th>.
--}}
@props(['column', 'align' => 'left'])

<th {{ $attributes->merge(['class' => 'px-4 py-3 font-semibold cursor-pointer select-none whitespace-nowrap hover:bg-white/10 transition-colors '.($align === 'right' ? 'text-right' : 'text-left')]) }}
    @click="sort('{{ $column }}')">
    <span class="inline-flex items-center gap-1 {{ $align === 'right' ? 'flex-row-reverse' : '' }}">
        <span>{{ $slot }}</span>
        <span class="text-[10px] opacity-70" x-text="sortIcon('{{ $column }}')"></span>
    </span>
</th>
