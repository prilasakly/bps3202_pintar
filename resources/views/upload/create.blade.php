@php
    // Format data subsidebar & indikators
    $subsidebarOptions = $sidebars->flatMap->subsidebars->map(fn ($sub) => [
        'id' => (int) $sub->id,
        'nama' => $sub->nama,
        'indikators' => $sub->indikators->map(fn ($i) => [
            'id' => (int) $i->id, 
            'nama' => $i->nama_judul
        ])->values(),
    ])->values();

    $oldIndikatorId = old('indikator_id') ? (int) old('indikator_id') : null;
    $oldSubsidebarId = null;
    
    if ($oldIndikatorId) {
        $foundSub = $subsidebarOptions->first(fn ($s) => collect($s['indikators'])->contains('id', $oldIndikatorId));
        $oldSubsidebarId = $foundSub['id'] ?? null;
    }

    // Pastikan nilai default berupa angka/integer atau null
    $initIndikatorId = $oldIndikatorId ?? ($indikatorTerpilih ? (int) $indikatorTerpilih->id : null);
    $initSubsidebarId = $oldSubsidebarId ?? ($indikatorTerpilih ? (int) $indikatorTerpilih->subsidebar_id : ($subsidebarTerpilih ? (int) $subsidebarTerpilih : null));
@endphp

@extends('layouts.app')

@section('title', 'Upload Data — PINTAR')
@section('eyebrow', 'Kelola Data')
@section('page-title', 'Upload Data Excel')

@section('content')
    {{-- Container terluar: Menggunakan flex flex-col & min-h-[calc(100vh-180px)] --}}
    <div class="w-full min-h-[calc(100vh-180px)] flex flex-col space-y-4">
        
        <p class="text-slate-500 text-sm shrink-0">
            Upload file <code class="px-1 py-0.5 rounded bg-slate-100 text-slate-600">.xls</code> / <code class="px-1 py-0.5 rounded bg-slate-100 text-slate-600">.xlsx</code>
            hasil ekspor BPS. Pilih dulu kategori datanya, lalu indikator tujuannya supaya lebih mudah ditemukan.
        </p>

        {{-- Guest / bukan tim IPDS: tidak boleh upload, arahkan login dulu --}}
        <template x-if="$store.auth.isGuest">
            <div class="p-5 rounded-xl bg-bps-orange-50 border border-bps-orange-200 text-bps-orange-800 text-sm flex items-center justify-between gap-3 flex-wrap shrink-0">
                <span>Anda harus login sebagai tim IPDS untuk mengupload data.</span>
                <button type="button" @click="$dispatch('open-login-modal')"
                        class="px-4 py-2 rounded-lg bg-bps-orange-500 text-white text-xs font-semibold hover:bg-bps-orange-600 transition shrink-0">
                    Masuk Sekarang
                </button>
            </div>
        </template>

        <template x-if="$store.auth.isLoggedIn && !$store.auth.isIpds">
            <div class="p-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm shrink-0">
                Akun Anda (<span x-text="$store.auth.roles.join(', ')"></span>) tidak memiliki akses untuk mengupload data.
                Hanya tim <strong>IPDS</strong> yang bisa mengelola data indikator.
            </div>
        </template>

        {{-- KARTU PUTIH: Diberi 'flex-1 flex flex-col justify-between' agar kotaknya ditarik penuh ke bawah --}}
        <div x-show="$store.auth.isIpds" x-cloak
             class="w-full flex-1 bg-white border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm flex flex-col justify-between"
             x-data="{
                 subsidebars: {{ Illuminate\Support\Js::from($subsidebarOptions) }},
                 subsidebarId: {{ Illuminate\Support\Js::from($initSubsidebarId) }},
                 indikatorId: {{ Illuminate\Support\Js::from($initIndikatorId) }},
                 tahun: '',
                 triwulan: '',
                 sheet: '',
                 force: false,
                 submitting: false,
                 pesan: null,
                 errors: {},

                 get indikatorList() {
                     if (!this.subsidebarId) return [];
                     const grp = this.subsidebars.find(s => Number(s.id) === Number(this.subsidebarId));
                     return grp ? grp.indikators : [];
                 },

                 onKategoriChange() {
                     this.indikatorId = null;
                 },

                 async submitUpload(e) {
                     this.submitting = true;
                     this.pesan = null;
                     this.errors = {};

                     const form = new FormData();
                     form.append('indikator_id', this.indikatorId ?? '');
                     form.append('tahun', this.tahun);
                     if (this.triwulan) form.append('triwulan', this.triwulan);
                     if (this.sheet) form.append('sheet', this.sheet);
                     if (this.force) form.append('force', '1');
                     const fileInput = this.$refs.file;
                     if (fileInput.files[0]) form.append('file', fileInput.files[0]);

                     try {
                         const res = await fetch('/api/upload', {
                             method: 'POST',
                             headers: {
                                 'Authorization': 'Bearer ' + $store.auth.token,
                                 'Accept': 'application/json',
                             },
                             body: form,
                         });
                         const data = await res.json();

                         if (res.status === 422 && data.errors) {
                             this.errors = data.errors;
                             this.pesan = { status: 'gagal', pesan: data.message || 'Periksa kembali isian form.' };
                         } else if (res.status === 403) {
                             this.pesan = { status: 'gagal', pesan: data.message || 'Anda tidak punya akses untuk aksi ini.' };
                         } else {
                             this.pesan = { status: data.status ?? (res.ok ? 'sukses' : 'gagal'), pesan: data.pesan ?? data.message ?? 'Terjadi kesalahan.' };
                             if (res.ok) {
                                 this.$refs.uploadForm.reset();
                                 this.tahun = ''; this.triwulan = ''; this.sheet = ''; this.force = false;
                             }
                         }
                     } catch (err) {
                         this.pesan = { status: 'gagal', pesan: 'Tidak bisa menghubungi server. Coba lagi.' };
                     } finally {
                         this.submitting = false;
                     }
                 }
             }">

            {{-- FORM: Diberi 'h-full flex flex-col justify-between' agar tombol berada di bagian paling bawah kartu --}}
            <form @submit.prevent="submitUpload()" x-ref="uploadForm" class="h-full flex flex-col justify-between space-y-6">

                <div class="space-y-6">
                    {{-- Alert Notifikasi Hasil Upload --}}
                    <template x-if="pesan">
                        <div class="p-4 rounded-xl text-sm border"
                             :class="{
                                'bg-bps-green-50 border-bps-green-200 text-bps-green-700': pesan.status === 'sukses',
                                'bg-bps-orange-50 border-bps-orange-200 text-bps-orange-700': pesan.status === 'diabaikan',
                                'bg-rose-50 border-rose-200 text-rose-700': pesan.status === 'gagal',
                             }" x-text="pesan.pesan"></div>
                    </template>

                    {{-- Form Grid 2 Kolom --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {{-- Langkah 1: Kategori --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">1. Kategori Data</label>
                            <select x-model.number="subsidebarId" @change="onKategoriChange()" required
                                    class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3.5 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500 transition">
                                <option :value="null" disabled>-- Pilih kategori --</option>
                                <template x-for="sub in subsidebars" :key="sub.id">
                                    <option :value="sub.id" 
                                            :selected="Number(sub.id) === Number(subsidebarId)"
                                            x-text="sub.nama + ' (' + sub.indikators.length + ')'"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Langkah 2: Indikator Tujuan --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">2. Indikator Tujuan</label>
                            <select name="indikator_id" x-model.number="indikatorId" :disabled="!subsidebarId" required
                                    class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3.5 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500 disabled:bg-slate-50 disabled:text-slate-400 transition">
                                <option :value="null" disabled>-- Pilih indikator --</option>
                                <template x-for="ind in indikatorList" :key="ind.id">
                                    <option :value="ind.id" 
                                            :selected="Number(ind.id) === Number(indikatorId)"
                                            x-text="ind.nama"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-slate-400" x-show="!subsidebarId">Pilih kategori data dulu di atas.</p>
                            <template x-if="errors.indikator_id"><p class="mt-1 text-xs text-rose-500" x-text="errors.indikator_id[0]"></p></template>
                        </div>

                        {{-- Tahun Data --}}
                        <div>
                            <label for="tahun" class="block text-sm font-semibold text-slate-700 mb-1.5">Tahun Data</label>
                            <input type="number" id="tahun" x-model="tahun" min="2000" max="2100" required
                                   placeholder="cth: 2025"
                                   class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3.5 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500 transition">
                            <template x-if="errors.tahun"><p class="mt-1 text-xs text-rose-500" x-text="errors.tahun[0]"></p></template>
                        </div>

                        {{-- Triwulan --}}
                        <div>
                            <label for="triwulan" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Triwulan <span class="text-slate-400 font-normal">(opsional)</span>
                            </label>
                            <select id="triwulan" x-model="triwulan"
                                    class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3.5 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500 transition">
                                <option value="">Data tahunan</option>
                                <option value="1">Triwulan 1</option>
                                <option value="2">Triwulan 2</option>
                                <option value="3">Triwulan 3</option>
                                <option value="4">Triwulan 4</option>
                            </select>
                            <template x-if="errors.triwulan"><p class="mt-1 text-xs text-rose-500" x-text="errors.triwulan[0]"></p></template>
                        </div>

                        {{-- Nama Sheet --}}
                        <div>
                            <label for="sheet" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Nama Sheet <span class="text-slate-400 font-normal">(opsional)</span>
                            </label>
                            <input type="text" id="sheet" x-model="sheet" placeholder="kosongkan untuk pakai sheet pertama"
                                   class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3.5 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500 transition">
                        </div>

                        {{-- File Excel --}}
                        <div>
                            <label for="file" class="block text-sm font-semibold text-slate-700 mb-1.5">File Excel</label>
                            <input type="file" id="file" x-ref="file" accept=".xls,.xlsx" required
                                   class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-bps-green-50 file:text-bps-green-700 hover:file:bg-bps-green-100 transition cursor-pointer">
                            <template x-if="errors.file"><p class="mt-1 text-xs text-rose-500" x-text="errors.file[0]"></p></template>
                            <p class="mt-1 text-xs text-slate-400">Maks 10MB. Format mengikuti template ekspor BPS.</p>
                        </div>

                    </div>
                </div>

                {{-- Bagian Bawah Form (Timpa Data & Tombol) --}}
                <div class="pt-6 border-t border-slate-100 space-y-4">
                    <div>
                        <label class="inline-flex items-center gap-2.5 text-sm text-slate-600 font-normal cursor-pointer">
                            <input type="checkbox" x-model="force" class="rounded border-slate-300 text-bps-green-600 focus:ring-bps-green-500 w-4 h-4">
                            Timpa data kalau tahun/triwulan ini sudah pernah diupload
                        </label>
                    </div>

                    <div>
                        <button type="submit" :disabled="submitting"
                                class="w-full md:w-auto min-w-[200px] inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-bps-green-500 text-white font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm disabled:opacity-60">
                            <span x-show="!submitting">Import Sekarang</span>
                            <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengupload...
                            </span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection