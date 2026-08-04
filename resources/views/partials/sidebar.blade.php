@php
    // Untuk pencarian cepat: flatten semua indikator dari sidebar bertipe dropdown (Data Makro)
    // jadi satu array datar (dipakai Alpine saat user mengetik).
    $flatIndikators = $navSidebars
        ->where('type', 'dropdown')
        ->flatMap(fn ($s) => $s->subsidebars)
        ->flatMap(function ($sub) {
            return $sub->indikators->map(fn ($i) => [
                'nama' => $i->nama_judul,
                'slug' => $i->slug,
                'grup' => $sub->nama,
                'url' => route('indikator.show', $i->slug),
            ]);
        })
        ->values();

    $currentIndikator = request()->route('indikator');
    $currentSubsidebarId = request()->route('subsidebar')?->id;
    $activeSubsidebarId = $currentIndikator?->subsidebar_id ?? $currentSubsidebarId;
    $initialOpenGroups = $activeSubsidebarId ? [(string) $activeSubsidebarId => true] : [];

    // Menu dropdown (Data Makro) otomatis terbuka kalau kita sedang di dalamnya.
    $initialOpenMenus = [];
    foreach ($navSidebars as $s) {
        if ($s->isDropdown() && $s->subsidebars->contains('id', $activeSubsidebarId)) {
            $initialOpenMenus[(string) $s->id] = true;
        }
    }
@endphp

<aside class="h-full w-80 lg:w-72 bg-white border-r border-slate-200 flex flex-col select-none"
       x-data="{ search: '', openGroups: {{ Illuminate\Support\Js::from($initialOpenGroups) }}, openMenus: {{ Illuminate\Support\Js::from($initialOpenMenus) }} }">

    {{-- Brand header --}}
    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">
        <a href="{{ route('indikator.index') }}" class="flex items-center gap-3 min-w-0">
            <span class="relative w-10 h-10 rounded-xl bg-bps-green-500 flex items-center justify-center shrink-0 shadow-sm">
                <span class="absolute -right-1 -top-1 w-3.5 h-3.5 rounded-full bg-bps-orange-500 border-2 border-white"></span>
                <span class="absolute -right-1 -bottom-1 w-3.5 h-3.5 rounded-full bg-bps-blue-500 border-2 border-white"></span>
                <span class="text-white font-extrabold text-sm">P</span>
            </span>
            <span class="min-w-0">
                <span class="block font-extrabold text-slate-900 tracking-tight leading-tight">PINTAR</span>
                <span class="block text-[11px] text-slate-400 truncate leading-tight">Data Makro BPS Sukabumi</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Search box: cari indikator di dalam kategori Data Makro --}}
    <div class="px-4 pt-4">
        <div class="relative">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" x-model="search" placeholder="Cari indikator..."
                   class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500">
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto custom-scrollbar px-4 py-4 space-y-1 text-sm">

        {{-- Menu utama: 100% dari database (sidebars), urut sesuai kolom "urutan" --}}
        <div x-show="!search.trim()" class="space-y-1">
            @foreach ($navSidebars as $menu)
                @if ($menu->isDropdown())
                    {{-- Menu dropdown, contoh: "Data Makro" -> daftar kategori (subsidebar) -> indikator --}}
                    <div>
                        <button type="button" @click="openMenus['{{ $menu->id }}'] = !openMenus['{{ $menu->id }}']"
                                class="w-full group relative flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl font-medium transition-all
                                       {{ $activeSubsidebarId ? 'bg-bps-green-50 text-bps-green-700 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span class="flex items-center gap-3">
                                @include('partials.icon', ['icon' => $menu->icon, 'class' => 'w-5 h-5 '.($activeSubsidebarId ? 'text-bps-green-600' : 'text-slate-400 group-hover:text-bps-green-600')])
                                <span>{{ $menu->nama }}</span>
                            </span>
                            <svg :class="openMenus['{{ $menu->id }}'] ? 'rotate-180 text-bps-green-600' : 'text-slate-400'"
                                 class="w-3.5 h-3.5 transition-transform duration-200 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="openMenus['{{ $menu->id }}']" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pl-4 ml-5 border-l border-slate-200 space-y-1 my-1">
                            @foreach ($menu->subsidebars as $sub)
                                <div>
                                    <button type="button" @click="openGroups['{{ $sub->id }}'] = !openGroups['{{ $sub->id }}']"
                                            class="w-full group flex items-center justify-between px-3 py-2 rounded-lg font-medium transition-all
                                                   {{ $activeSubsidebarId === $sub->id ? 'text-bps-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                        <span class="flex items-center gap-2.5 text-left">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $activeSubsidebarId === $sub->id ? 'bg-bps-orange-500' : 'bg-slate-300' }}"></span>
                                            <span class="text-[13px]">{{ $sub->nama }}</span>
                                        </span>
                                        <span class="flex items-center gap-1.5 shrink-0">
                                            <span class="text-[10px] text-slate-400">{{ $sub->indikators_count }}</span>
                                            <svg :class="openGroups['{{ $sub->id }}'] ? 'rotate-180 text-bps-green-600' : 'text-slate-400'"
                                                 class="w-3.5 h-3.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </span>
                                    </button>

                                    <div x-show="openGroups['{{ $sub->id }}']" x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         class="pl-5 ml-4 border-l border-slate-200 space-y-0.5 my-1">
                                        @forelse ($sub->indikators as $indikator)
                                            <a href="{{ route('indikator.show', $indikator) }}"
                                               class="block px-3 py-1.5 rounded-lg text-xs leading-snug transition-colors
                                                      {{ $currentIndikator?->id === $indikator->id
                                                          ? 'text-bps-green-700 font-semibold bg-bps-green-50'
                                                          : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                                                {{ $indikator->nama_judul }}
                                            </a>
                                        @empty
                                            <p class="px-3 py-1.5 text-xs text-slate-400 italic">Belum ada indikator.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    {{-- Menu link biasa, contoh: "Beranda", "Buku Tamu", "Tautan Penting" --}}
                    @php
                        $isActive = $menu->route_name && request()->routeIs($menu->route_name);
                        // "Tautan Penting" sengaja disembunyikan dari guest (belum login) -- lihat
                        // permintaan RBAC: guest tidak menampilkan tautan penting. Status login
                        // dicek di client (Alpine store "auth") karena auth aplikasi ini murni
                        // via token API, bukan session server.
                        $wajibLogin = $menu->slug === 'tautan-penting';
                    @endphp
                    <a href="{{ $menu->href() }}"
                       @if ($wajibLogin) x-show="$store.auth.isLoggedIn" x-cloak @endif
                       class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all
                              {{ $isActive ? 'bg-bps-green-50 text-bps-green-700 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                        @if ($isActive)
                            <span class="absolute left-0 top-2 bottom-2 w-1 bg-bps-green-500 rounded-r-full"></span>
                        @endif
                        @include('partials.icon', ['icon' => $menu->icon, 'class' => 'w-5 h-5 '.($isActive ? 'text-bps-green-600' : 'text-slate-400 group-hover:text-bps-green-600')])
                        <span>{{ $menu->nama }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Hasil pencarian (flat, muncul saat user mengetik) --}}
        <div x-show="search.trim()" x-cloak>
            <p class="px-1 text-[10px] font-bold tracking-widest text-slate-400 uppercase mb-2 mt-3">Hasil Pencarian</p>
            <template x-for="item in ({{ Illuminate\Support\Js::from($flatIndikators) }}).filter(i => i.nama.toLowerCase().includes(search.toLowerCase().trim()))" :key="item.slug">
                <a :href="item.url" class="block px-3 py-2 rounded-lg hover:bg-slate-50 mb-0.5">
                    <span class="block text-xs font-medium text-slate-700" x-text="item.nama"></span>
                    <span class="block text-[10px] text-bps-blue-600" x-text="item.grup"></span>
                </a>
            </template>
            <template x-if="({{ Illuminate\Support\Js::from($flatIndikators) }}).filter(i => i.nama.toLowerCase().includes(search.toLowerCase().trim())).length === 0">
                <p class="px-3 py-2 text-xs text-slate-400 italic">Tidak ditemukan.</p>
            </template>
        </div>
    </nav>

    <div class="m-4 p-3 rounded-xl bg-bps-green-50 border border-bps-green-100 flex items-center gap-3">
        <div class="p-2 rounded-lg bg-white text-bps-green-600 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <div class="overflow-hidden">
            <p class="text-[11px] font-semibold text-bps-green-800 truncate">PINTAR v1.0</p>
            <p class="text-[10px] text-bps-green-600 truncate">BPS Kabupaten Sukabumi</p>
        </div>
    </div>
</aside>
