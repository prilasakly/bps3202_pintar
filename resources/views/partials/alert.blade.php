@php
    // Menyesuaikan status dari IndikatorExcelImporter ('sukses' | 'gagal' | 'diabaikan') ke gaya tampilan.
    $style = match ($status ?? '') {
        'sukses' => ['bg-bps-green-50', 'border-bps-green-200', 'text-bps-green-700', 'icon' => 'M9 12.75L11.25 15 15 9.75'],
        'diabaikan' => ['bg-bps-orange-50', 'border-bps-orange-200', 'text-bps-orange-700', 'icon' => 'M12 9v3.75m0 3.75h.007v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        default => ['bg-rose-50', 'border-rose-200', 'text-rose-700', 'icon' => 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    };
@endphp

<div class="p-4 rounded-xl border {{ $style[0] }} {{ $style[1] }} {{ $style[2] }} text-sm flex items-start gap-2.5">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $style['icon'] }}" />
    </svg>
    <span>{{ $pesan }}</span>
</div>
