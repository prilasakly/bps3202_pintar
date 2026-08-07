<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PINTAR — Portal Data Makro BPS')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Palet warna identitas BPS: hijau (utama), oranye (aksen), biru (sekunder), putih (dasar).
                        bps: {
                            green: { 50: '#EAF7EF', 100: '#CFEEDA', 200: '#9FDCB6', 300: '#6FC993', 500: '#0B7A3B', 600: '#0A6B34', 700: '#075C2C', 900: '#053A1C' },
                            orange: { 50: '#FFF5E8', 100: '#FFE3B8', 200: '#FFCB7A', 300: '#FFB84D', 500: '#F7941E', 600: '#E07E0C', 700: '#B8640A' },
                            blue: { 50: '#E9F4FB', 100: '#C0E0F2', 200: '#8DC6E8', 300: '#5CACDD', 500: '#0072BC', 600: '#03619F', 700: '#045686' },
                        },
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script defer>
        // Auth store global (Alpine). Semua proses login/logout/cek-role pakai token dari
        // /api/login (Sanctum bearer token), BUKAN session Laravel -- supaya nanti mobile app
        // bisa pakai endpoint API yang persis sama. Token disimpan di localStorage browser.
        //
        // "Guest" = tidak ada token tersimpan (belum pernah login / sudah logout).
        document.addEventListener('alpine:init', () => {
            Alpine.store('auth', {
                token: localStorage.getItem('pintar_token'),
                user: JSON.parse(localStorage.getItem('pintar_user') || 'null'),
                roles: JSON.parse(localStorage.getItem('pintar_roles') || '[]'),
                // Daftar slug permission (dari tabel permissions lewat role-role user ini,
                // dikirim oleh AuthApiController). INILAH yang bikin visibilitas halaman/
                // tombol bisa diatur dari halaman "Kelola Hak Akses" (web) tanpa ubah kode --
                // beda dengan "roles" di atas yang tetap/hardcoded di database seeder.
                permissions: JSON.parse(localStorage.getItem('pintar_permissions') || '[]'),
                loading: false,
                error: '',

                get isLoggedIn() {
                    return !!this.token;
                },

                get isGuest() {
                    return !this.token;
                },

                hasRole(role) {
                    return this.roles.includes(role);
                },

                get isIpds() {
                    return this.hasRole('ipds');
                },

                get isSuperadmin() {
                    return this.hasRole('superadmin');
                },

                // Cek permission (BUKAN nama role) -- pakai ini untuk mengatur tampil/
                // tidaknya halaman atau tombol yang aksesnya ingin bisa diubah lewat
                // halaman "Kelola Hak Akses", tanpa perlu ubah kode. Contoh pakai di Blade:
                //   x-show="$store.auth.can('data.manage')"
                // Terima satu slug ('data.manage') atau beberapa (array, OR -- salah satu
                // cukup): ['data.manage', 'data.upload'].
                can(permission) {
                    const perms = Array.isArray(permission) ? permission : [permission];
                    return this.isSuperadmin || perms.some((p) => this.permissions.includes(p));
                },

                async login(email, password) {
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch('/api/login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ email, password, device_name: 'web' }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.error = data.message || Object.values(data.errors ?? {}).flat()[0] || 'Login gagal.';
                            return false;
                        }
                        this.setSession(data.token, data.user);
                        return true;
                    } catch (e) {
                        this.error = 'Tidak bisa menghubungi server. Coba lagi.';
                        return false;
                    } finally {
                        this.loading = false;
                    }
                },

                async logout() {
                    try {
                        await fetch('/api/logout', {
                            method: 'POST',
                            headers: { 'Authorization': 'Bearer ' + this.token, 'Accept': 'application/json' },
                        });
                    } catch (e) {
                        // biarpun request gagal (misal offline), tetap hapus sesi lokal.
                    }
                    this.clearSession();
                    window.location.href = '/';
                },

                setSession(token, user) {
                    this.token = token;
                    this.user = user;
                    this.roles = user.roles || [];
                    this.permissions = user.permissions || [];
                    localStorage.setItem('pintar_token', token);
                    localStorage.setItem('pintar_user', JSON.stringify(user));
                    localStorage.setItem('pintar_roles', JSON.stringify(this.roles));
                    localStorage.setItem('pintar_permissions', JSON.stringify(this.permissions));
                },

                clearSession() {
                    this.token = null;
                    this.user = null;
                    this.roles = [];
                    this.permissions = [];
                    localStorage.removeItem('pintar_token');
                    localStorage.removeItem('pintar_user');
                    localStorage.removeItem('pintar_roles');
                    localStorage.removeItem('pintar_permissions');
                },
            });
        });
    </script>

    <script defer>
        // === Sistem tabel generik (search + sort + pagination) =============================
        // Dua "mixin" plain-JS yang bisa dipakai ulang di halaman mana pun yang punya tabel
        // dengan data banyak, tinggal manggil salah satu di x-data sesuai kebutuhan:
        //
        // 1) window.pintarTableFactory(config)
        //    Buat tabel yang datanya diambil dari API (search/sort/pagination diproses di
        //    server lewat trait BuildsTableQuery). Cocok kalau datanya besar / bisa terus
        //    bertambah. Bisa dipakai langsung: x-data="dataTable({ endpoint: '/api/users' })",
        //    ATAU di-spread ke komponen Alpine lain yang butuh state tambahan (lihat contoh
        //    "userManagement" di resources/views/user/index.blade.php):
        //        Alpine.data('userManagement', () => ({
        //            ...window.pintarTableFactory({ endpoint: '/api/users', defaultSort: 'name' }),
        //            // ...state & method khusus halaman itu...
        //        }))
        //
        //    Konsumen HARUS memanggil this.initTable() sendiri (di dalam init() masing-masing)
        //    supaya bisa dikontrol kapan fetch pertama kali jalan (misal nunggu user login dulu).
        //
        //    Properti yang tersedia buat dipakai di HTML: items, loading, error, search, page,
        //    perPage, perPageOptions, total, from, to, lastPage, pageNumbers, sortBy, sortDir.
        //    Method: initTable(), fetchData(), sort(column), sortIcon(column), goToPage(n).
        //
        // 2) window.pintarClientTableFactory(rows, options)
        //    Buat tabel yang datanya sudah lengkap ada di HTML/JS (di-render server lalu di-
        //    embed sebagai JSON, tanpa fetch API terpisah) -- cocok untuk data yang sudah pasti
        //    kecil/sedang dan sudah kepalang di-load, misal tabel pivot indikator per kecamatan.
        //    Search/sort/pagination-nya full diproses di browser (client-side).
        //
        //    Method & properti yang tersedia sama seperti di atas (tanpa loading/error/fetchData,
        //    karena tidak ada request jaringan).
        window.pintarTableFactory = function (config = {}) {
            return {
                items: [],
                loading: false,
                error: '',
                search: '',
                sortBy: config.defaultSort ?? null,
                sortDir: config.defaultDir === 'desc' ? 'desc' : 'asc',
                page: 1,
                perPage: config.perPage ?? 10,
                perPageOptions: config.perPageOptions ?? [10, 25, 50, 100],
                total: 0,
                lastPage: 1,
                from: 0,
                to: 0,
                _endpoint: config.endpoint,
                _authRequired: config.authRequired !== false,
                _extraParams: config.extraParams ?? {},
                _searchTimer: null,

                // Panggil ini sendiri di init() halaman yang makai (bukan otomatis), supaya
                // fetch pertama bisa ditunda sampai kondisi tertentu terpenuhi (mis. sudah login).
                initTable() {
                    this.$watch('search', () => {
                        this.page = 1;
                        clearTimeout(this._searchTimer);
                        this._searchTimer = setTimeout(() => this.fetchData(), 350);
                    });
                    this.$watch('perPage', () => {
                        this.page = 1;
                        this.fetchData();
                    });
                    this.fetchData();
                },

                sort(column) {
                    if (!column) return;
                    if (this.sortBy === column) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = column;
                        this.sortDir = 'asc';
                    }
                    this.page = 1;
                    this.fetchData();
                },

                sortIcon(column) {
                    if (this.sortBy !== column) return '⇅';
                    return this.sortDir === 'asc' ? '↑' : '↓';
                },

                goToPage(p) {
                    p = Number(p);
                    if (!p || p < 1 || p > this.lastPage || p === this.page) return;
                    this.page = p;
                    this.fetchData();
                },

                get pageNumbers() {
                    return pintarPageNumbers(this.page, this.lastPage);
                },

                async fetchData() {
                    if (!this._endpoint) return;
                    this.loading = true;
                    this.error = '';
                    try {
                        const params = new URLSearchParams();
                        params.set('page', this.page);
                        params.set('per_page', this.perPage);
                        if (this.search) params.set('search', this.search);
                        if (this.sortBy) {
                            params.set('sort_by', this.sortBy);
                            params.set('sort_dir', this.sortDir);
                        }
                        Object.entries(this._extraParams || {}).forEach(([key, value]) => {
                            if (value !== null && value !== undefined && value !== '') {
                                params.set(key, value);
                            }
                        });

                        const headers = { 'Accept': 'application/json' };
                        if (this._authRequired) {
                            headers['Authorization'] = 'Bearer ' + Alpine.store('auth').token;
                        }

                        const res = await fetch(this._endpoint + '?' + params.toString(), { headers });
                        if (!res.ok) throw new Error('Gagal memuat data.');
                        const data = await res.json();

                        this.items = data.data ?? [];
                        this.total = data.total ?? this.items.length;
                        this.lastPage = data.last_page ?? 1;
                        this.from = data.from ?? (this.items.length ? 1 : 0);
                        this.to = data.to ?? this.items.length;
                        this.page = data.current_page ?? this.page;
                    } catch (e) {
                        this.error = config.errorMessage ?? 'Tidak bisa memuat data. Coba muat ulang halaman.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        };

        window.pintarClientTableFactory = function (rows = [], options = {}) {
            return {
                _allRows: rows ?? [],
                search: '',
                searchKeys: options.searchKeys ?? ['label'],
                sortBy: options.defaultSort ?? null,
                sortDir: options.defaultDir === 'desc' ? 'desc' : 'asc',
                page: 1,
                perPage: options.perPage ?? 10,
                perPageOptions: options.perPageOptions ?? [10, 25, 50, 100],

                init() {
                    this.$watch('search', () => { this.page = 1; });
                    this.$watch('perPage', () => { this.page = 1; });
                },

                _toNumber(v) {
                    if (v === null || v === undefined || v === '') return null;
                    let s = String(v).trim();
                    // Format Indonesia (titik ribuan, koma desimal) misal "1.234,5"
                    if (/^-?\d{1,3}(\.\d{3})*(,\d+)?$/.test(s)) {
                        s = s.replace(/\./g, '').replace(',', '.');
                    } else {
                        s = s.replace(',', '.');
                    }
                    const n = parseFloat(s);
                    return isNaN(n) ? null : n;
                },

                _valueFor(row, key) {
                    if (key === 'label') return row.label;
                    return row.cells ? row.cells[key] : row[key];
                },

                get filteredRows() {
                    const term = this.search.trim().toLowerCase();
                    if (!term) return this._allRows;
                    return this._allRows.filter((row) => {
                        const haystack = [];
                        this.searchKeys.forEach((k) => haystack.push(row[k]));
                        if (row.cells) haystack.push(...Object.values(row.cells));
                        return haystack.some((v) => v !== null && v !== undefined && String(v).toLowerCase().includes(term));
                    });
                },

                get sortedRows() {
                    const rows = [...this.filteredRows];
                    if (!this.sortBy) return rows;
                    const dir = this.sortDir === 'desc' ? -1 : 1;
                    rows.sort((a, b) => {
                        const va = this._valueFor(a, this.sortBy);
                        const vb = this._valueFor(b, this.sortBy);
                        const na = this._toNumber(va);
                        const nb = this._toNumber(vb);
                        if (na !== null && nb !== null) return (na - nb) * dir;
                        return String(va ?? '').localeCompare(String(vb ?? ''), 'id') * dir;
                    });
                    return rows;
                },

                get total() { return this.filteredRows.length; },
                get lastPage() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
                get from() { return this.total === 0 ? 0 : (this.page - 1) * this.perPage + 1; },
                get to() { return Math.min(this.page * this.perPage, this.total); },
                get pageItems() {
                    const start = (this.page - 1) * this.perPage;
                    return this.sortedRows.slice(start, start + this.perPage);
                },
                get pageNumbers() {
                    return pintarPageNumbers(this.page, this.lastPage);
                },

                sort(key) {
                    if (this.sortBy === key) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = key;
                        this.sortDir = 'asc';
                    }
                    this.page = 1;
                },
                sortIcon(key) {
                    if (this.sortBy !== key) return '⇅';
                    return this.sortDir === 'asc' ? '↑' : '↓';
                },
                goToPage(p) {
                    p = Number(p);
                    if (!p || p < 1 || p > this.lastPage) return;
                    this.page = p;
                },
            };
        };

        // Helper bersama: bikin daftar nomor halaman dengan "..." kalau halamannya banyak,
        // dipakai baik oleh pintarTableFactory maupun pintarClientTableFactory.
        function pintarPageNumbers(current, total) {
            const delta = 1;
            const range = [];
            for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
                range.push(i);
            }
            const withDots = [];
            if (current - delta > 2) {
                withDots.push(1, '...');
            } else {
                withDots.push(1);
            }
            withDots.push(...range);
            if (current + delta < total - 1) {
                withDots.push('...', total);
            } else if (total > 1) {
                withDots.push(total);
            }
            return [...new Set(withDots)];
        }

        // Komponen siap-pakai buat tabel read-only sederhana yang cukup pakai fetch API polos
        // (tanpa CRUD/state tambahan): <div x-data="dataTable({ endpoint: '/api/...' })">
        document.addEventListener('alpine:init', () => {
            Alpine.data('dataTable', (config) => ({
                ...window.pintarTableFactory(config),
                init() {
                    this.initTable();
                },
            }));
        });
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .bps-dot-strip { background: linear-gradient(90deg, #0B7A3B 0 33%, #F7941E 33% 66%, #0072BC 66% 100%); }
    </style>

    @stack('styles')
</head>
<body class="bg-slate-50 min-h-screen text-slate-800" x-data="{ sidebarOpen: false }">

    {{-- Garis tipis 3 warna identitas BPS di paling atas halaman --}}
    <div class="h-1 bps-dot-strip"></div>

    <div class="flex h-[calc(100vh-4px)] overflow-hidden">

        {{-- Sidebar (desktop selalu tampil, mobile lewat overlay) --}}
        <div class="hidden lg:block">
            @include('partials.sidebar')
        </div>

        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="absolute inset-0 bg-slate-900/50" @click="sidebarOpen = false"></div>
            <div class="relative z-50 h-full">
                @include('partials.sidebar')
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top navbar --}}
            <header class="flex items-center justify-between gap-4 px-4 md:px-8 py-4 bg-white border-b border-slate-200 shadow-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-bps-green-700 hover:bg-bps-green-50 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-bps-orange-600 uppercase tracking-wider">@yield('eyebrow', 'Portal Data Statistik')</p>
                        <h1 class="text-base md:text-lg font-bold text-slate-900 truncate">@yield('page-title', 'PINTAR')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0" x-data="{ userMenuOpen: false }">
                    {{-- Upload Data: cuma tampil buat user yang punya permission "data.upload"
                         (default role ipds, bisa diubah lewat halaman Kelola Hak Akses) --}}
                    <a href="{{ route('upload.create') }}" x-cloak x-show="$store.auth.can('data.upload')"
                       class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-bps-green-500 text-white text-xs font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" />
                        </svg>
                        Upload Data
                    </a>

                    {{-- Guest: tombol Masuk, buka modal login (bukan pindah halaman) --}}
                    <button type="button" x-cloak x-show="$store.auth.isGuest"
                            @click="$dispatch('open-login-modal')"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-bps-green-500 text-bps-green-700 text-xs font-semibold hover:bg-bps-green-50 active:scale-[0.98] transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H3" />
                        </svg>
                        Masuk
                    </button>

                    {{-- Sudah login: dropdown nama user + role + tombol keluar --}}
                    <div class="relative" x-cloak x-show="$store.auth.isLoggedIn" @click.outside="userMenuOpen = false">
                        <button type="button" @click="userMenuOpen = !userMenuOpen"
                                class="inline-flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 transition">
                            <span class="w-7 h-7 rounded-full bg-bps-green-100 text-bps-green-700 flex items-center justify-center text-xs font-bold shrink-0"
                                  x-text="($store.auth.user?.name || '?').charAt(0).toUpperCase()"></span>
                            <span class="hidden sm:block text-left leading-tight">
                                <span class="block text-xs font-semibold text-slate-700" x-text="$store.auth.user?.name"></span>
                                <span class="block text-[10px] text-slate-400" x-text="$store.auth.roles.join(', ')"></span>
                            </span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="userMenuOpen" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-52 bg-white border border-slate-200 rounded-xl shadow-lg py-1.5 z-50">
                            <div class="px-3.5 py-2 border-b border-slate-100">
                                <p class="text-xs font-semibold text-slate-700" x-text="$store.auth.user?.email"></p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Tim: <span x-text="$store.auth.roles.join(', ') || '-'"></span></p>
                            </div>
                            <button type="button" @click="userMenuOpen = false; $store.auth.logout()"
                                    class="w-full text-left px-3.5 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition">
                                Keluar
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar">
                @if (session('notifikasi'))
                    <div class="mb-6">
                        @include('partials.alert', [
                            'status' => session('notifikasi')['status'] ?? 'sukses',
                            'pesan' => session('notifikasi')['pesan'] ?? '',
                        ])
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.login-modal')

    @stack('scripts')
</body>
</html>
