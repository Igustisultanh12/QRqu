<template>
    <AdminLayout>
        <template #header>DOKU Gateway Management & Kredensial</template>

        <div class="space-y-6 max-w-4xl">
            <!-- Connection Test Result Banner -->
            <div v-if="test_result" class="p-6 rounded-3xl border" :class="test_result.success ? 'bg-emerald-950/80 border-emerald-500/50 text-emerald-300' : 'bg-rose-950/80 border-rose-500/50 text-rose-300'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg v-if="test_result.success" class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <div>
                            <h4 class="font-bold text-sm">{{ test_result.message }}</h4>
                            <p class="text-xs opacity-80 mt-0.5">Waktu uji koneksi: {{ test_result.timestamp }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DOKU Settings Card -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-white">Konfigurasi Merchant DOKU</h3>
                        <p class="text-xs text-slate-400">Parameter sinkronisasi protokol Mas Sultan / romei 1 untuk generate QRIS Live</p>
                    </div>

                    <button
                        @click="testConnection"
                        type="button"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition flex items-center space-x-2 shrink-0 shadow-md"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Test Connection</span>
                    </button>
                </div>

                <form @submit.prevent="submitConfig" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">DOKU Environment</label>
                            <input
                                :value="config.environment"
                                disabled
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-400 uppercase font-mono cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">DOKU Client ID</label>
                            <input
                                v-model="form.client_id"
                                type="text"
                                required
                                placeholder="Contoh: MALLID-12345"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white font-mono focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">DOKU Base URL (API Gateway)</label>
                        <input
                            v-model="form.base_url"
                            type="url"
                            required
                            placeholder="https://api.doku.com atau https://api-sandbox.doku.com"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white font-mono focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            DOKU Secret Key (Masked: <span class="text-amber-400 font-mono">{{ config.secret_key_masked || 'KOSONG' }}</span>)
                        </label>
                        <input
                            v-model="form.secret_key"
                            type="password"
                            placeholder="Ketik secret baru jika ingin mengganti/memperbarui"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white font-mono focus:outline-none focus:border-indigo-500"
                        />
                        <span class="text-[11px] text-slate-500 mt-1 block">Secret Key dienkripsi dan tidak akan ditampilkan secara plaintext demi keamanan.</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <span class="text-[11px] text-slate-400 block font-semibold mb-1">Inbound DOKU Webhook URL:</span>
                            <div class="bg-slate-900 p-2.5 rounded-xl border border-slate-800 text-xs font-mono text-emerald-400 select-all truncate">
                                {{ config.webhook_url }}
                            </div>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-400 block font-semibold mb-1">Request Timeout:</span>
                            <div class="bg-slate-900 p-2.5 rounded-xl border border-slate-800 text-xs font-mono text-slate-300">
                                {{ config.timeout_seconds }} Detik (Maks retry: {{ config.retry_limit }}x)
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition shadow-md"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi DOKU' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
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
