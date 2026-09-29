<template>
    <CustomerLayout>
        <template #header>Pengaturan & Log Pengiriman Webhook</template>

        <div class="space-y-6">
            <!-- Webhook Settings Card -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfigurasi Webhook URL</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">QRqu akan mengirim notifikasi pembayaran real-time bertanda tangan HMAC ke URL ini.</p>
                    </div>

                    <button
                        @click="sendTestPing"
                        :disabled="isPinging || !form.url"
                        type="button"
                        class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/40 rounded-xl text-xs font-semibold transition shrink-0 flex items-center space-x-2"
                    >
                        <svg v-if="isPinging" class="animate-spin h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ isPinging ? 'Menguji Koneksi...' : 'Kirim Test Ping Webhook' }}</span>
                    </button>
                </div>

                <form @submit.prevent="saveWebhook" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Webhook Endpoint URL (HTTPS Wajib)</label>
                        <input
                            v-model="form.url"
                            type="url"
                            required
                            placeholder="https://www.romei1.my.id/api/webhook/qrqu"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Pastikan domain dan path dapat diakses publik dari server QRqu.</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Webhook Secret (Verifikasi Signature HMAC-SHA256)</label>
                            <button
                                v-if="form.secret"
                                type="button"
                                @click="copySecret"
                                class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <span>{{ copied ? 'Tersalin!' : 'Salin Secret' }}</span>
                            </button>
                        </div>
                        <input
                            v-model="form.secret"
                            type="text"
                            placeholder="Biarkan kosong untuk generate secret baru otomatis"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-emerald-600 dark:text-emerald-400 font-mono focus:outline-none focus:border-emerald-500"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Tempelkan Webhook Secret ini pada Pengaturan Admin platform merchant (Romei) Anda.</p>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs transition shadow-sm"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Webhook' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Webhook Delivery History Logs -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm transition-colors duration-200">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Log Pengiriman Webhook Terakhir</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catatan pengiriman webhook lengkap dengan status HTTP, response body, latency, dan retry</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Event & ID</th>
                                <th class="p-4">URL Sasaran</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">HTTP</th>
                                <th class="p-4">Percobaan</th>
                                <th class="p-4">Latency</th>
                                <th class="p-4">Waktu</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="!deliveries?.data || deliveries.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Belum ada log pengiriman webhook.
                                </td>
                            </tr>
                            <tr v-for="d in deliveries.data" :key="d.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white uppercase">{{ d.event }}</div>
                                    <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500">{{ d.event_id }}</div>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-700 dark:text-slate-300 truncate max-w-[200px]" :title="d.url">{{ d.url }}</td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            d.status === 'DELIVERED' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                            d.status === 'RETRYING' || d.status === 'PENDING' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                                            'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ d.status }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono font-bold" :class="d.http_status >= 200 && d.http_status < 300 ? 'text-emerald-600 dark:text-emerald-400' : (d.status === 'PENDING' ? 'text-amber-500' : 'text-rose-600 dark:text-rose-400')">
                                    {{ d.http_status ? d.http_status : (d.status === 'PENDING' ? 'MENUNGGU' : 'TIMEOUT') }}
                                </td>
                                <td class="p-4 text-slate-500 dark:text-slate-400">{{ d.attempt }} / {{ d.max_attempts }}</td>
                                <td class="p-4 text-slate-500 dark:text-slate-400 font-mono">{{ d.duration_ms ? d.duration_ms + 'ms' : '-' }}</td>
                                <td class="p-4 text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ new Date(d.created_at).toLocaleString('id-ID') }}</td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="viewDetail(d)"
                                            type="button"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700"
                                        >
                                            Detail
                                        </button>
                                        <button
                                            v-if="d.status !== 'DELIVERED'"
                                            @click="retryDelivery(d)"
                                            :disabled="retryingId === d.id"
                                            type="button"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/20 dark:hover:bg-amber-500/30 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold transition border border-amber-200 dark:border-amber-500/40 disabled:opacity-50"
                                        >
                                            {{ retryingId === d.id ? 'Mengirim...' : 'Kirim Ulang' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="selectedDelivery" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[85vh] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Detail Pengiriman Webhook</h4>
                        <p class="font-mono text-xs text-slate-400">{{ selectedDelivery.event_id }}</p>
                    </div>
                    <button @click="selectedDelivery = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">
                        &times;
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Event & Target URL</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 uppercase">{{ selectedDelivery.event }}</span>
                        <p class="font-mono text-[11px] text-slate-600 dark:text-slate-400 break-all mt-0.5">{{ selectedDelivery.url }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-xl">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Status & Latency</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="font-bold" :class="selectedDelivery.http_status >= 200 && selectedDelivery.http_status < 300 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                HTTP {{ selectedDelivery.http_status || 'N/A' }}
                            </span>
                            <span class="text-slate-400">• {{ selectedDelivery.duration_ms ? selectedDelivery.duration_ms + 'ms' : '-' }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500">Percobaan: {{ selectedDelivery.attempt }} / {{ selectedDelivery.max_attempts }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payload Request (JSON yang dikirim)</label>
                    <pre class="p-3 bg-slate-950 text-emerald-400 rounded-xl text-[11px] font-mono overflow-x-auto max-h-48 border border-slate-800">{{ JSON.stringify(selectedDelivery.payload, null, 2) }}</pre>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Response Body dari Server Merchant</label>
                    <pre class="p-3 bg-slate-950 text-slate-300 rounded-xl text-[11px] font-mono overflow-x-auto max-h-48 border border-slate-800 whitespace-pre-wrap">{{ selectedDelivery.response_body || '(Tidak ada response body / koneksi gagal)' }}</pre>
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        @click="selectedDelivery = null"
                        type="button"
                        class="px-4 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-semibold transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    webhook: Object,
    deliveries: Object,
});

const isPinging = ref(false);
const retryingId = ref(null);
const copied = ref(false);
const selectedDelivery = ref(null);

const form = useForm({
    url: props.webhook?.url || '',
    secret: props.webhook?.secret || '',
});

const saveWebhook = () => {
    form.post(route('customer.webhooks.store'));
};

const sendTestPing = () => {
    isPinging.value = true;
    router.post(route('customer.webhooks.test-ping'), {}, {
        onFinish: () => {
            isPinging.value = false;
        }
    });
};

const retryDelivery = (delivery) => {
    retryingId.value = delivery.id;
    router.post(route('customer.webhooks.retry', delivery.id), {}, {
        onFinish: () => {
            retryingId.value = null;
        }
    });
};

const viewDetail = (delivery) => {
    selectedDelivery.value = delivery;
};

const copySecret = () => {
    if (form.secret) {
        navigator.clipboard.writeText(form.secret);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};
</script>
