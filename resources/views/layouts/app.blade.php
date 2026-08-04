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
                    localStorage.setItem('pintar_token', token);
                    localStorage.setItem('pintar_user', JSON.stringify(user));
                    localStorage.setItem('pintar_roles', JSON.stringify(this.roles));
                },

                clearSession() {
                    this.token = null;
                    this.user = null;
                    this.roles = [];
                    localStorage.removeItem('pintar_token');
                    localStorage.removeItem('pintar_user');
                    localStorage.removeItem('pintar_roles');
                },
            });
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
                    {{-- Upload Data: cuma tampil buat user yang login dan berperan ipds --}}
                    <a href="{{ route('upload.create') }}" x-cloak x-show="$store.auth.isIpds"
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
