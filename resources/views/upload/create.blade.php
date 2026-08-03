@php
    // Untuk cascading select: subsidebar > indikator, diratakan jadi satu array supaya gampang dipakai Alpine.
    $subsidebarOptions = $sidebars->flatMap->subsidebars->map(fn ($sub) => [
        'id' => $sub->id,
        'nama' => $sub->nama,
        'indikators' => $sub->indikators->map(fn ($i) => ['id' => $i->id, 'nama' => $i->nama_judul])->values(),
    ])->values();

    // Kalau form gagal validasi, cari lagi subsidebar dari indikator_id yang tadi dipilih
    // supaya dropdown kategori & indikator tetap terisi (bukan kembali kosong).
    $oldIndikatorId = old('indikator_id');
    $oldSubsidebarId = null;
    if ($oldIndikatorId) {
        $foundSub = $subsidebarOptions->first(fn ($s) => collect($s['indikators'])->contains('id', (int) $oldIndikatorId));
        $oldSubsidebarId = $foundSub['id'] ?? null;
    }

    $initSubsidebarId = $oldSubsidebarId ?? $subsidebarTerpilih ?? null;
    $initIndikatorId = $oldIndikatorId ?? optional($indikatorTerpilih)->id ?? null;
@endphp

@extends('layouts.app')

@section('title', 'Upload Data — PINTAR')
@section('eyebrow', 'Kelola Data')
@section('page-title', 'Upload Data Excel')

@section('content')
    <div class="max-w-2xl">
        <p class="text-slate-500 text-sm">
            Upload file <code class="px-1 py-0.5 rounded bg-slate-100 text-slate-600">.xls</code> / <code class="px-1 py-0.5 rounded bg-slate-100 text-slate-600">.xlsx</code>
            hasil ekspor BPS. Pilih dulu kategori datanya, lalu indikator tujuannya supaya lebih mudah ditemukan.
        </p>

        @if (session('hasil_import'))
            @php($hasil = session('hasil_import'))
            <div class="mt-6">
                @include('partials.alert', ['status' => $hasil['status'], 'pesan' => $hasil['pesan']])
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6 bg-white border border-slate-200 rounded-2xl p-6"
             x-data="{
                 subsidebars: {{ Illuminate\Support\Js::from($subsidebarOptions) }},
                 subsidebarId: {{ Illuminate\Support\Js::from($initSubsidebarId) }},
                 indikatorId: {{ Illuminate\Support\Js::from($initIndikatorId) }},
                 get indikatorList() {
                     const grp = this.subsidebars.find(s => s.id === this.subsidebarId);
                     return grp ? grp.indikators : [];
                 },
             }">

            <form method="post" action="{{ route('upload.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- Langkah 1: kategori --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">1. Kategori Data</label>
                    <select x-model.number="subsidebarId" @change="indikatorId = null" required
                            class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                        <option :value="null" disabled selected>-- Pilih kategori --</option>
                        <template x-for="sub in subsidebars" :key="sub.id">
                            <option :value="sub.id" x-text="sub.nama + ' (' + sub.indikators.length + ')'"></option>
                        </template>
                    </select>
                </div>

                {{-- Langkah 2: indikator, terfilter sesuai kategori terpilih --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">2. Indikator Tujuan</label>
                    <select name="indikator_id" x-model.number="indikatorId" :disabled="!subsidebarId" required
                            class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500 disabled:bg-slate-50 disabled:text-slate-400">
                        <option :value="null" disabled selected>-- Pilih indikator --</option>
                        <template x-for="ind in indikatorList" :key="ind.id">
                            <option :value="ind.id" x-text="ind.nama"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-slate-400" x-show="!subsidebarId">Pilih kategori data dulu di atas.</p>
                    @error('indikator_id') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="tahun" class="block text-sm font-semibold text-slate-700 mb-1.5">Tahun Data</label>
                        <input type="number" name="tahun" id="tahun" min="2000" max="2100" required
                               value="{{ old('tahun') }}" placeholder="cth: 2025"
                               class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                        @error('tahun') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="triwulan" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Triwulan <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <select name="triwulan" id="triwulan"
                                class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                            <option value="">Data tahunan</option>
                            <option value="1" @selected(old('triwulan') == 1)>Triwulan 1</option>
                            <option value="2" @selected(old('triwulan') == 2)>Triwulan 2</option>
                            <option value="3" @selected(old('triwulan') == 3)>Triwulan 3</option>
                            <option value="4" @selected(old('triwulan') == 4)>Triwulan 4</option>
                        </select>
                        @error('triwulan') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="sheet" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Nama Sheet <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input type="text" name="sheet" id="sheet" value="{{ old('sheet') }}" placeholder="kosongkan untuk pakai sheet pertama"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
                </div>

                <div>
                    <label for="file" class="block text-sm font-semibold text-slate-700 mb-1.5">File Excel</label>
                    <input type="file" name="file" id="file" accept=".xls,.xlsx" required
                           class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-bps-green-50 file:text-bps-green-700 hover:file:bg-bps-green-100">
                    @error('file') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-400">Maks 10MB. Format mengikuti template ekspor BPS.</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600 font-normal">
                    <input type="checkbox" name="force" value="1" class="rounded border-slate-300 text-bps-green-600 focus:ring-bps-green-500">
                    Timpa data kalau tahun/triwulan ini sudah pernah diupload
                </label>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-bps-green-500 text-white font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm">
                    Import Sekarang
                </button>
            </form>
        </div>
    </div>
@endsection
