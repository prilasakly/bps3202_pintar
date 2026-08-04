{{-- Modal login global. Dibuka dari tombol "Masuk" di navbar (event open-login-modal),
     atau dari halaman lain yang butuh user login dulu sebelum lanjut aksi. --}}
<div x-data="{
        open: false,
        email: '',
        password: '',
        async submit() {
            const ok = await $store.auth.login(this.email, this.password);
            if (ok) {
                this.open = false;
                this.email = '';
                this.password = '';
                window.dispatchEvent(new CustomEvent('login-success'));
            }
        },
     }"
     @open-login-modal.window="open = true; $store.auth.error = ''"
     x-show="open" x-cloak
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4"
     @keydown.escape.window="open = false">

    <div @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">

        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Masuk</h3>
                <p class="text-xs text-slate-400 mt-0.5">Login untuk kelola data indikator (khusus tim IPDS).</p>
            </div>
            <button type="button" @click="open = false" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form @submit.prevent="submit()" class="mt-5 space-y-4">
            <div x-show="$store.auth.error" x-cloak class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs" x-text="$store.auth.error"></div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                <input type="email" x-model="email" required autocomplete="username"
                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500"
                       placeholder="nama@bps.go.id">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                <input type="password" x-model="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2.5 focus:ring-2 focus:ring-bps-green-500/40 focus:border-bps-green-500"
                       placeholder="••••••••">
            </div>

            <button type="submit" :disabled="$store.auth.loading"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-bps-green-500 text-white font-semibold hover:bg-bps-green-600 active:scale-[0.98] transition shadow-sm disabled:opacity-60">
                <span x-show="!$store.auth.loading">Masuk</span>
                <span x-show="$store.auth.loading" x-cloak>Memproses...</span>
            </button>
        </form>
    </div>
</div>
