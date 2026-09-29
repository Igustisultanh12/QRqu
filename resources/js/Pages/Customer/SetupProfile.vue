<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 p-8 rounded-3xl space-y-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <span class="font-black text-2xl">Q</span>
                </div>
                <h2 class="text-xl font-bold text-white">Lengkapi Profil Merchant Anda</h2>
                <p class="text-xs text-slate-400 mt-1">Satu langkah lagi untuk mulai mengintegrasikan gateway QRIS QRqu.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Bisnis / Toko</label>
                    <input
                        v-model="form.company_name"
                        type="text"
                        required
                        placeholder="Contoh: Tokoku Indonesia"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">No. WhatsApp</label>
                    <input
                        v-model="form.whatsapp"
                        type="text"
                        required
                        placeholder="08123456789"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Alamat Singkat</label>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-emerald-500/20"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan & Lanjutkan' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    company_name: '',
    phone: '',
    whatsapp: '',
    address: '',
});

const submit = () => {
    form.patch(route('profile.update'));
};
</script>
