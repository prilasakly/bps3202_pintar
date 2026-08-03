@extends('layouts.app')

@section('title', $subsidebar->nama . ' — PINTAR')
@section('eyebrow', $subsidebar->sidebar->nama ?? 'Kategori Data')
@section('page-title', $subsidebar->nama)

@section('content')
    <nav class="text-xs text-slate-400 flex items-center gap-1.5">
        <a href="{{ route('indikator.index') }}" class="hover:text-bps-green-600">Beranda</a>
        <span>/</span>
        <span class="text-slate-500">{{ $subsidebar->nama }}</span>
    </nav>

    <div class="mt-2 flex items-center justify-between flex-wrap gap-3">
        <div>
            <p class="text-xs font-semibold text-bps-orange-600 uppercase tracking-wide">Kategori Data</p>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $subsidebar->nama }}</h1>
        </div>
        <a href="{{ route('upload.create', ['kategori' => $subsidebar->id]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-bps-green-500 text-white text-sm font-semibold hover:bg-bps-green-600 transition">
            + Upload Data Baru
        </a>
    </div>

    @if ($indikators->isEmpty())
        <div class="mt-8 p-8 rounded-2xl border border-dashed border-slate-300 text-center">
            <p class="text-slate-500">Belum ada indikator di kategori ini.</p>
        </div>
    @else
        <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($indikators as $ind)
                <a href="{{ route('indikator.show', $ind) }}"
                   class="p-5 rounded-2xl border border-slate-200 bg-white hover:border-bps-green-300 hover:shadow-md transition-all group">
                    <h3 class="font-semibold text-slate-900 group-hover:text-bps-green-700 text-sm leading-snug">
                        {{ $ind->nama_judul }}
                    </h3>
                    <p class="mt-2 text-xs text-slate-400">
                        {{ $ind->periode_count }} periode data
                        @if ($ind->satuan)
                            &middot; Satuan: {{ $ind->satuan }}
                        @endif
                    </p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
