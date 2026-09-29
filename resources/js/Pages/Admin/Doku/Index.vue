<template>
    <AdminLayout>
        <template #header>Konfigurasi Gateway & Kredensial</template>

        <div class="space-y-6 max-w-4xl">
            <!-- Connection Test Result Banner -->
            <div v-if="test_result" class="p-6 rounded-3xl border" :class="test_result.success ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-800 dark:text-emerald-300' : 'bg-rose-500/10 border-rose-500/30 text-rose-800 dark:text-rose-300'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg v-if="test_result.success" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <div>
                            <h4 class="font-bold text-sm">{{ test_result.message }}</h4>
                            <p class="text-xs opacity-80 mt-0.5">Waktu uji koneksi: {{ test_result.timestamp }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gateway Settings Card -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 space-y-6 shadow-sm">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfigurasi Parameter Gateway</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Parameter sinkronisasi protokol untuk generate QRIS Live & Sandbox</p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            @click="testConnection"
                            type="button"
                            class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full text-xs font-bold transition flex items-center space-x-1.5 shadow-sm active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Test Connection</span>
                        </button>
                        <Link
                            :href="route('admin.settings.index')"
                            class="px-4 py-2 bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 rounded-full text-xs font-bold transition flex items-center space-x-1.5 shadow-sm active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            <span>Test Pembayaran QRIS</span>
                        </Link>
                    </div>
                </div>

                <form @submit.prevent="submitConfig" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Gateway Environment</label>
                            <input
                                :value="config.environment"
                                disabled
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-500 uppercase font-mono cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Gateway Client ID</label>
                            <input
                                v-model="form.client_id"
                                type="text"
                                required
                                placeholder="Contoh: MALLID-12345"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Gateway Base URL (API Endpoint)</label>
                        <input
                            v-model="form.base_url"
                            type="url"
                            required
                            placeholder="https://api.gateway.com atau https://api-sandbox.gateway.com"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Gateway Secret Key (Masked: <span class="text-amber-500 font-mono">{{ config.secret_key_masked || 'KOSONG' }}</span>)
                        </label>
                        <input
                            v-model="form.secret_key"
                            type="password"
                            placeholder="Ketik secret baru jika ingin mengganti/memperbarui"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-indigo-500"
                        />
                        <span class="text-[11px] text-slate-500 mt-1 block">Secret Key dienkripsi dan tidak akan ditampilkan secara plaintext demi keamanan.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block font-semibold mb-1">Inbound Webhook URL:</span>
                            <div class="bg-slate-50 dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-mono text-emerald-600 dark:text-emerald-400 select-all truncate">
                                {{ config.webhook_url }}
                            </div>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block font-semibold mb-1">Request Timeout:</span>
                            <div class="bg-slate-50 dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-mono text-slate-700 dark:text-slate-300">
                                {{ config.timeout_seconds }} Detik (Maks retry: {{ config.retry_limit }}x)
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs rounded-full transition shadow-sm active:scale-95"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi Gateway' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    config: Object,
    test_result: Object,
});

const form = useForm({
    client_id: props.config?.client_id || '',
    secret_key: '',
    base_url: props.config?.base_url || '',
});

const submitConfig = () => {
    form.post(route('admin.doku.update'), {
        onSuccess: () => form.reset('secret_key'),
    });
};

const testConnection = () => {
    router.post(route('admin.doku.test-connection'));
};
</script>
