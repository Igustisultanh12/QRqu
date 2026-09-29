<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4 antialiased">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl space-y-6">
            <div class="text-center">
                <Link :href="route('home')" class="inline-flex items-center space-x-2 mb-4">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <span class="font-black text-2xl text-slate-950">Q</span>
                    </div>
                    <span class="font-extrabold text-2xl tracking-tight bg-gradient-to-r from-emerald-400 to-teal-200 bg-clip-text text-transparent">QRqu</span>
                </Link>
                <h1 class="text-xl font-bold text-white">Masuk ke Portal Gateway</h1>
                <p class="text-xs text-slate-400 mt-1">Akses dashboard merchant atau panel administrator</p>
            </div>

            <div v-if="status" class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs">
                {{ status }}
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(err, f) in form.errors" :key="f">{{ err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        placeholder="admin@qrqu.id atau merchant@tokoku.com"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-semibold text-slate-300">Password</label>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-[11px] text-emerald-400 hover:underline">
                            Lupa password?
                        </Link>
                    </div>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div class="flex items-center">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded text-emerald-500 bg-slate-950 border-slate-700 focus:ring-emerald-500"
                        />
                        <span class="text-xs text-slate-400">Ingat sesi saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20"
                >
                    {{ form.processing ? 'Memproses Masuk...' : 'Masuk ke Dashboard' }}
                </button>
            </form>

            <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
                Belum memiliki akun merchant?
                <Link :href="route('register')" class="text-emerald-400 hover:underline font-semibold ml-1">
                    Daftar di sini
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

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