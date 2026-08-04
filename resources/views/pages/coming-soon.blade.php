@extends('layouts.app')

@section('title', $judul . ' — PINTAR')
@section('eyebrow', 'Segera Hadir')
@section('page-title', $judul)

@section('content')
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-950 via-bps-green-900 to-slate-900 p-6 sm:p-8 text-white shadow-md my-2">
    {{-- Background Glow --}}
    <div class="absolute -top-20 -right-20 w-72 h-72 bg-bps-green-500/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-xl mx-auto text-center py-2">
        {{-- Badge Status --}}
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-emerald-300 text-[11px] font-medium mb-4">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
            </span>
            Fitur Dalam Tahap Pengembangan
        </div>

        {{-- Icon --}}
        <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-white/10 backdrop-blur-lg border border-white/15 flex items-center justify-center text-emerald-400 shadow-sm">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l5.654-4.654m0 0l3.03-2.496c.14-.468.382-.891.766-1.208L17.25 3m-5.83 8.17L3 17.25" />
            </svg>
        </div>

        {{-- Judul & Deskripsi --}}
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white mb-2">
            {{ $judul }}
        </h1>
        <p class="text-slate-300 text-xs sm:text-sm leading-relaxed mb-6 max-w-md mx-auto">
            {{ $deskripsi ?? 'Kumpulan tautan penting (link) terkait BPS Kabupaten Sukabumi akan tersedia di sini.' }}
        </p>

        {{-- Feature Sneak Peek --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-left mb-6">
            <div class="bg-white/5 border border-white/10 rounded-xl p-3 backdrop-blur-sm">
                <div class="text-emerald-400 mb-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h2 class="text-xs font-semibold text-white">Akses Cepat</h2>
                <p class="text-[10px] text-slate-300 mt-0.5">Optimasi pemrosesan data instan</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-xl p-3 backdrop-blur-sm">
                <div class="text-emerald-400 mb-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h2 class="text-xs font-semibold text-white">Visualisasi Ringkas</h2>
                <p class="text-[10px] text-slate-300 mt-0.5">Grafik & tabel yang informatif</p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-xl p-3 backdrop-blur-sm">
                <div class="text-emerald-400 mb-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h2 class="text-xs font-semibold text-white">Ekspor Fleksibel</h2>
                <p class="text-[10px] text-slate-300 mt-0.5">Dukungan format Excel & PDF</p>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div>
            <a href="{{ route('indikator.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs transition shadow-sm active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Beranda
            </a>
        </div>
    </div>
</div>
@endsection