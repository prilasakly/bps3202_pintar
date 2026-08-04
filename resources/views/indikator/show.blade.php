@extends('layouts.app')

@section('title', $indikator->nama_judul . ' — PINTAR')
@section('eyebrow', $indikator->subsidebar->nama ?? 'Indikator')
@section('page-title', $indikator->nama_judul)

@section('content')
    <nav class="text-xs text-slate-400 flex items-center gap-1.5 flex-wrap">
        <a href="{{ route('indikator.index') }}" class="hover:text-bps-green-600">Beranda</a>
        <span>/</span>
        @if ($indikator->subsidebar)
            <a href="{{ route('indikator.subsidebar', $indikator->subsidebar) }}" class="hover:text-bps-green-600">
                {{ $indikator->subsidebar->nama }}
            </a>
            <span>/</span>
        @endif
        <span class="text-slate-500">{{ $indikator->nama_judul }}</span>
    </nav>

    <div class="mt-2 flex items-start justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ $indikator->nama_judul }}</h1>
            @if ($indikator->satuan)
                <p class="text-xs text-slate-400 mt-1">Satuan: {{ $indikator->satuan }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if ($periodeDipilih->isNotEmpty())
                <a href="{{ route('indikator.export', $indikator) }}?{{ http_build_query(['tahun' => $tahunDipilih]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-bps-blue-300 bg-bps-blue-50 text-bps-blue-700 text-sm font-semibold hover:bg-bps-blue-100 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export
                </a>
            @endif
            <a href="{{ route('upload.create', ['kategori' => $indikator->subsidebar_id, 'indikator' => $indikator->slug]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
                </svg>
                Upload Data
            </a>
        </div>
    </div>

    {{-- Filter tahun --}}
    <form method="get" class="mt-6 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-500 mr-1">Tahun:</span>
        @forelse ($tahunTersediaUnik as $tahun)
            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium cursor-pointer transition
                          {{ in_array($tahun, $tahunDipilih)
                              ? 'bg-bps-green-50 border-bps-green-300 text-bps-green-700'
                              : 'border-slate-300 text-slate-500 hover:bg-slate-50' }}">
                <input type="checkbox" name="tahun[]" value="{{ $tahun }}" class="hidden"
                       onchange="this.form.submit()" @checked(in_array($tahun, $tahunDipilih))>
                {{ $tahun }}
            </label>
        @empty
            <p class="text-sm text-slate-400 italic">Belum ada data yang diupload untuk indikator ini.</p>
        @endforelse
    </form>

    {{-- Tabel Pivot --}}
    @if ($periodeDipilih->isNotEmpty())
        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 custom-scrollbar">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-bps-green-600 text-white">
                        <th rowspan="2" class="px-4 py-3 text-left font-semibold sticky left-0 bg-bps-green-600">
                            Kecamatan
                        </th>
                        @foreach ($periodeDipilih as $p)
                            <th colspan="{{ $indikator->kolom->count() }}" class="px-4 py-2 text-center font-semibold border-l border-bps-green-500">
                                {{ $p->tahun }}{{ $p->triwulan ? ' - TW'.$p->triwulan : '' }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="bg-bps-green-700 text-white text-xs">
                        @foreach ($periodeDipilih as $p)
                            @foreach ($indikator->kolom as $k)
                                <th class="px-3 py-2 text-center font-medium border-l border-bps-green-600">{{ $k->kolom_label }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indikator->baris as $baris)
                        <tr class="odd:bg-white even:bg-bps-green-50/40 hover:bg-bps-orange-50/60 transition-colors">
                            <td class="px-4 py-2.5 font-medium text-slate-700 sticky left-0 bg-inherit">
                                {{ $baris->baris_label }}
                            </td>
                            @foreach ($periodeDipilih as $p)
                                @foreach ($indikator->kolom as $k)
                                    <td class="px-3 py-2.5 text-right text-slate-600 border-l border-slate-100">
                                        {{ $nilaiMap[$baris->id][$p->id][$k->id] ?? '-' }}
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 1 + $periodeDipilih->count() * max($indikator->kolom->count(), 1) }}" class="px-4 py-8 text-center text-slate-400">
                                Belum ada baris data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif ($tahunTersediaUnik->isNotEmpty())
        <div class="mt-6 p-8 rounded-2xl border border-dashed border-slate-300 text-center">
            <p class="text-slate-500">Pilih minimal satu tahun di atas untuk menampilkan tabel.</p>
        </div>
    @else
        <div class="mt-6 p-8 rounded-2xl border border-dashed border-slate-300 text-center">
            <p class="text-slate-500">Belum ada data untuk indikator ini.</p>
            <a href="{{ route('upload.create', ['kategori' => $indikator->subsidebar_id, 'indikator' => $indikator->slug]) }}" class="mt-3 inline-block text-sm font-semibold text-bps-green-600 hover:underline">
                Upload data pertama →
            </a>
        </div>
    @endif

    {{-- Kelola Data per Periode: export & hapus per tahun/triwulan --}}
    @if ($semuaPeriode->isNotEmpty())
        <div class="mt-8"
             x-data="{ modalOpen: false, modalLabel: '', modalFormId: null }"
             @open-delete-modal.window="modalOpen = true; modalLabel = $event.detail.label; modalFormId = $event.detail.formId">

            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Kelola Data per Periode</h2>
            <p class="text-xs text-slate-400 mt-1">Export atau hapus data untuk tahun/triwulan tertentu saja.</p>

            <div class="mt-3 rounded-2xl border border-slate-200 bg-white divide-y divide-slate-100 overflow-hidden">
                @foreach ($semuaPeriode as $p)
                    @php
                        $labelPeriode = $p->tahun.($p->triwulan ? ' - Triwulan '.$p->triwulan : ' (Tahunan)');
                        $formId = 'form-hapus-periode-'.$p->id;
                    @endphp
                    <div class="flex items-center justify-between gap-3 px-4 py-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">{{ $labelPeriode }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                @if ($p->file_asal)
                                    File: {{ $p->file_asal }} &middot;
                                @endif
                                Diupload {{ $p->diupload_pada?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ route('indikator.export', $indikator) }}?{{ http_build_query(['tahun' => [$p->tahun]]) }}"
                               title="Export periode ini"
                               class="p-2 rounded-lg text-bps-blue-600 hover:bg-bps-blue-50 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </a>
                            <button type="button" title="Hapus periode ini"
                                    @click="$dispatch('open-delete-modal', { formId: '{{ $formId }}', label: '{{ $labelPeriode }}' })"
                                    class="p-2 rounded-lg text-rose-500 hover:bg-rose-50 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                            <form id="{{ $formId }}" method="POST"
                                  action="{{ route('indikator.periode.destroy', [$indikator, $p]) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Modal konfirmasi hapus --}}
            <div x-show="modalOpen" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
                <div @click.outside="modalOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </span>
                        <h3 class="font-bold text-slate-900">Hapus data periode?</h3>
                    </div>
                    <p class="text-sm text-slate-500 mt-3">
                        Data periode <span class="font-semibold text-slate-700" x-text="modalLabel"></span>
                        untuk indikator ini akan dihapus permanen dan tidak bisa dikembalikan.
                    </p>
                    <div class="mt-5 flex gap-2 justify-end">
                        <button type="button" @click="modalOpen = false"
                                class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </button>
                        <button type="button" @click="document.getElementById(modalFormId).submit()"
                                class="px-4 py-2 rounded-lg text-sm font-semibold bg-rose-600 text-white hover:bg-rose-700 transition">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
