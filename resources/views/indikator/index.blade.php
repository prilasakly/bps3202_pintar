@extends('layouts.app')

@section('title', 'Beranda — PINTAR')
@section('eyebrow', 'Selamat Datang')
@section('page-title', 'Portal Data Makro')

@section('content')
    {{-- Hero --}}
    <div class="rounded-2xl bg-gradient-to-br from-bps-green-600 to-bps-green-500 p-6 md:p-8 text-white relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-bps-orange-500/20"></div>
        <div class="absolute right-16 bottom-0 w-24 h-24 rounded-full bg-bps-blue-500/25"></div>
        <div class="relative">
            <p class="text-xs font-semibold text-bps-orange-200 uppercase tracking-wider">Data Makro Kabupaten Sukabumi</p>
            <h1 class="text-2xl md:text-3xl font-extrabold mt-1.5 max-w-xl">Jelajahi indikator statistik BPS per kategori</h1>
            <p class="mt-2 text-bps-green-50/90 max-w-xl text-sm">
                Semua indikator dikelompokkan supaya mudah dicari. Pilih kategori di bawah, atau upload data Excel baru.
            </p>
            <a href="{{ route('upload.create') }}" x-show="$store.auth.isIpds" x-class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white text-bps-green-700 text-sm font-semibold hover:bg-bps-green-50 active:scale-[0.98] transition shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
                </svg>
                Upload Data Baru
            </a>
        </div>
    </div>

    {{-- Ringkasan angka --}}
    <div class="mt-6 grid sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl border border-slate-200 bg-white">
            <p class="text-xs text-slate-400">Kategori Data</p>
            <p class="text-2xl font-bold text-bps-green-700 mt-1">{{ $stats['total_subsidebar'] }}</p>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 bg-white">
            <p class="text-xs text-slate-400">Total Indikator</p>
            <p class="text-2xl font-bold text-bps-blue-700 mt-1">{{ $stats['total_indikator'] }}</p>
        </div>
        <div class="p-5 rounded-2xl border border-slate-200 bg-white">
            <p class="text-xs text-slate-400">Dataset Terupload</p>
            <p class="text-2xl font-bold text-bps-orange-600 mt-1">{{ $stats['total_dataset'] }}</p>
        </div>
    </div>

    {{-- Dataset / indikator terbaru --}}
    @if ($indikatorTerbaru->isNotEmpty())
        <h2 class="mt-8 text-sm font-bold text-slate-700 uppercase tracking-wide">Baru Diupload</h2>
        <div class="mt-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($indikatorTerbaru as $ind)
                <a href="{{ route('indikator.show', $ind) }}"
                   class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-bps-green-300 hover:shadow-md transition-all group">
                    <p class="text-[11px] text-bps-blue-600 font-medium">{{ $ind->subsidebar->nama }}</p>
                    <h3 class="mt-1 font-semibold text-slate-900 group-hover:text-bps-green-700 text-sm leading-snug">
                        {{ $ind->nama_judul }}
                    </h3>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Daftar kategori (subsidebar) --}}
    <h2 class="mt-8 text-sm font-bold text-slate-700 uppercase tracking-wide">Kategori Data</h2>
    <div class="mt-3 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($sidebars->flatMap->subsidebars as $sub)
            <a href="{{ route('indikator.subsidebar', $sub) }}"
               class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-bps-green-300 hover:shadow-md transition-all group flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="font-semibold text-slate-900 group-hover:text-bps-green-700 text-sm leading-snug">
                        {{ $sub->nama }}
                    </h3>
                    <p class="mt-1.5 text-xs text-slate-400">{{ $sub->indikators_count }} indikator</p>
                </div>
                <span class="shrink-0 w-8 h-8 rounded-lg bg-bps-green-50 text-bps-green-600 flex items-center justify-center group-hover:bg-bps-green-500 group-hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            </a>
        @empty
            <p class="text-sm text-slate-400 italic">Belum ada kategori. Jalankan seeder terlebih dahulu.</p>
        @endforelse
    </div>
@endsection
