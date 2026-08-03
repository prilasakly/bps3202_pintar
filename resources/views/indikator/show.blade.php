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
        <a href="{{ route('upload.create', ['indikator' => $indikator->slug]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 transition shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
            </svg>
            Upload Data
        </a>
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
            <a href="{{ route('upload.create', ['indikator' => $indikator->slug]) }}" class="mt-3 inline-block text-sm font-semibold text-bps-green-600 hover:underline">
                Upload data pertama →
            </a>
        </div>
    @endif
@endsection
