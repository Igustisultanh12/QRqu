<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4 antialiased">
        <div class="max-w-xl w-full bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl space-y-6">
            <div class="text-center">
                <Link :href="route('home')" class="inline-flex items-center space-x-2 mb-4">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <span class="font-black text-2xl text-slate-950">Q</span>
                    </div>
                    <span class="font-extrabold text-2xl tracking-tight bg-gradient-to-r from-emerald-400 to-teal-200 bg-clip-text text-transparent">QRqu</span>
                </Link>
                <h1 class="text-xl font-bold text-white">Daftar Akun Merchant</h1>
                <p class="text-xs text-slate-400 mt-1">Mulai integrasikan pembayaran QRIS DOKU ke sistem Anda hari ini</p>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-300 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    <li v-for="(err, f) in form.errors" :key="f">{{ err }}</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="space-y-4 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Nama Lengkap *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="John Doe"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Nama Bisnis / Perusahaan</label>
                        <input
                            v-model="form.company_name"
                            type="text"
                            placeholder="PT Tokoku Digital"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Alamat Email *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="merchant@example.com"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">No. WhatsApp / HP *</label>
                        <input
                            v-model="form.whatsapp"
                            type="text"
                            required
                            placeholder="081234567890"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Alamat Kantor / Toko</label>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        placeholder="Alamat lengkap usaha Anda"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Password *</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            minlength="8"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Konfirmasi Password *</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20 mt-2"
                >
                    {{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar Sekarang' }}
                </button>
            </form>

            <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
                Sudah memiliki akun merchant?
                <Link :href="route('login')" class="text-emerald-400 hover:underline font-semibold ml-1">
                    Masuk di sini
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

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