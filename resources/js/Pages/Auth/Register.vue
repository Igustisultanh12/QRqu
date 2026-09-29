<template>
    <div class="min-h-screen bg-[#FAF8F5] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex items-center justify-center p-4 antialiased transition-colors duration-300 relative overflow-hidden font-sans">
        <!-- Ambient Atmosphere Gradient Glows -->
        <div class="pointer-events-none absolute -top-32 -left-32 w-96 h-96 bg-gradient-to-br from-orange-400/20 via-pink-400/15 to-transparent rounded-full blur-3xl dark:from-orange-500/10"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-32 w-96 h-96 bg-gradient-to-bl from-teal-400/20 via-emerald-400/15 to-transparent rounded-full blur-3xl dark:from-emerald-500/10"></div>

        <!-- Floating Theme Switcher -->
        <div class="absolute top-6 right-6 z-20">
            <ThemeToggle />
        </div>

        <div class="relative z-10 max-w-xl w-full bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 dark:shadow-black/60 space-y-6 transition-all duration-300">
            <div class="text-center">
                <Link :href="route('home')" class="inline-flex items-center space-x-2.5 mb-4 group">
                    <div class="w-11 h-11 rounded-2xl bg-slate-950 dark:bg-white flex items-center justify-center shadow-md group-hover:scale-105 transition-transform">
                        <span class="font-black text-xl text-white dark:text-slate-950 font-mono">Q</span>
                    </div>
                    <div class="flex items-baseline">
                        <span class="font-black text-2xl tracking-tighter text-slate-950 dark:text-white">QRqu</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500 ml-1"></span>
                    </div>
                </Link>
                <h1 class="text-2xl font-black text-slate-950 dark:text-white tracking-tight">Daftar Akun Merchant</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Mulai integrasikan pembayaran QRIS otomatis ke sistem Anda hari ini</p>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-300 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(err, f) in form.errors" :key="f">{{ err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="John Doe"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Bisnis / Perusahaan</label>
                        <input
                            v-model="form.company_name"
                            type="text"
                            placeholder="PT Tokoku Digital"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Email *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="merchant@example.com"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. WhatsApp / HP *</label>
                        <input
                            v-model="form.whatsapp"
                            type="text"
                            required
                            placeholder="081234567890"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Kantor / Toko</label>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        placeholder="Alamat lengkap usaha Anda"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Password *</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            minlength="8"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Konfirmasi Password *</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold text-xs tracking-tight transition shadow-md active:scale-95 flex items-center justify-center space-x-2 mt-4"
                >
                    <span>{{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar Sekarang' }}</span>
                    <span>➔</span>
                </button>
            </form>

            <div class="text-center text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-slate-800">
                Sudah memiliki akun merchant?
                <Link :href="route('login')" class="text-emerald-600 dark:text-emerald-400 hover:underline font-semibold ml-1">
                    Masuk di sini
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const form = useForm({
    name: '',
    company_name: '',
    email: '',
    phone: '',
    whatsapp: '',
    address: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>