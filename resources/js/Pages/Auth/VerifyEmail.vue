<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email Akun - QRqu" />

        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3 shadow-sm">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Verifikasi Alamat Email Anda</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                Terima kasih telah mendaftar di <strong class="text-emerald-600 dark:text-emerald-400 font-bold">QRqu Gateway</strong>!
            </p>
        </div>

        <div class="mb-5 p-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300 leading-relaxed text-center">
            Kami telah mengirimkan tautan verifikasi ke email
            <span class="font-bold text-slate-900 dark:text-white font-mono block mt-1 text-sm">{{ $page.props.auth.user?.email }}</span>
            Silakan buka kotak masuk (atau folder spam) email Anda dan klik tombol verifikasi untuk mengaktifkan akun merchant Anda.
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-5 p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-xs font-semibold text-emerald-700 dark:text-emerald-300 text-center flex items-center justify-center gap-2"
        >
            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Tautan verifikasi baru berhasil dikirim ulang ke email Anda!</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-md disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>{{ form.processing ? 'Mengirim Ulang...' : 'Kirim Ulang Email Verifikasi' }}</span>
            </button>

            <div class="text-center pt-2">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition"
                >
                    Keluar / Gunakan Akun Lain
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
