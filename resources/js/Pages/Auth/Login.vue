<template>
    <div class="min-h-screen bg-[#FAF8F5] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex items-center justify-center p-4 antialiased transition-colors duration-300 relative overflow-hidden font-sans">
        <!-- Ambient Atmosphere Gradient Glows -->
        <div class="pointer-events-none absolute -top-32 -left-32 w-96 h-96 bg-gradient-to-br from-orange-400/20 via-pink-400/15 to-transparent rounded-full blur-3xl dark:from-orange-500/10"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-32 w-96 h-96 bg-gradient-to-bl from-teal-400/20 via-emerald-400/15 to-transparent rounded-full blur-3xl dark:from-emerald-500/10"></div>

        <!-- Floating Theme Switcher -->
        <div class="absolute top-6 right-6 z-20">
            <ThemeToggle />
        </div>

        <div class="relative z-10 max-w-md w-full bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 p-8 sm:p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 dark:shadow-black/60 space-y-6 transition-all duration-300">
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
                <h1 class="text-2xl font-black text-slate-950 dark:text-white tracking-tight">Masuk ke Portal Gateway</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Akses dashboard merchant atau panel administrator</p>
            </div>

            <div v-if="status" class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-600 dark:text-emerald-400 text-xs">
                {{ status }}
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-300 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(err, f) in form.errors" :key="f">{{ err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        placeholder="admin@qrqu.id atau merchant@tokoku.com"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-semibold text-slate-700 dark:text-slate-300">Password</label>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-[11px] text-emerald-600 dark:text-emerald-400 hover:underline">
                            Lupa password?
                        </Link>
                    </div>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div class="flex items-center">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded text-emerald-500 bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 focus:ring-emerald-500"
                        />
                        <span class="text-xs text-slate-600 dark:text-slate-400">Ingat sesi saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold text-xs tracking-tight transition shadow-md active:scale-95 flex items-center justify-center space-x-2"
                >
                    <span>{{ form.processing ? 'Memproses Masuk...' : 'Masuk ke Dashboard' }}</span>
                    <span>➔</span>
                </button>
            </form>

            <div class="text-center text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-slate-800">
                Belum memiliki akun merchant?
                <Link :href="route('register')" class="text-emerald-600 dark:text-emerald-400 hover:underline font-semibold ml-1">
                    Daftar di sini
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>