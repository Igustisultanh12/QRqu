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
                        type="button"
                        class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/40 rounded-xl text-xs font-semibold transition shrink-0 flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Kirim Test Ping Webhook</span>
                    </button>
                </div>

                <form @submit.prevent="saveWebhook" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Webhook Endpoint URL (HTTPS Wajib)</label>
                        <input
                            v-model="form.url"
                            type="url"
                            required
                            placeholder="https://merchant.example.com/api/payment/webhook"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Webhook Secret (Digunakan untuk Verifikasi Signature HMAC-SHA256)</label>
                        <input
                            v-model="form.secret"
                            type="text"
                            placeholder="Biarkan kosong untuk generate secret baru otomatis"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-emerald-600 dark:text-emerald-400 font-mono focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-sm"
                        >
                            Simpan Pengaturan Webhook
                        </button>
                    </div>
                </form>
            </div>

            <!-- Webhook Delivery History Logs -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm transition-colors duration-200">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Log Pengiriman Webhook Terakhir</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catatan pengiriman tembakan webhook lengkap dengan status HTTP dan fitur retry otomatis</p>
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
                            <tr v-if="deliveries.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Belum ada log pengiriman webhook.
                                </td>
                            </tr>
                            <tr v-for="d in deliveries.data" :key="d.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white uppercase">{{ d.event }}</div>
                                    <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500">{{ d.event_id }}</div>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-700 dark:text-slate-300 truncate max-w-[200px]">{{ d.url }}</td>
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
                                <td class="p-4 font-mono font-bold" :class="d.http_status >= 200 && d.http_status < 300 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ d.http_status || 'ERR' }}
                                </td>
                                <td class="p-4 text-slate-500 dark:text-slate-400">{{ d.attempt }} / {{ d.max_attempts }}</td>
                                <td class="p-4 text-slate-500 dark:text-slate-400 font-mono">{{ d.duration_ms ? d.duration_ms + 'ms' : '-' }}</td>
                                <td class="p-4 text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ new Date(d.created_at).toLocaleString('id-ID') }}</td>
                                <td class="p-4 text-right">
                                    <button
                                        v-if="d.status !== 'DELIVERED'"
                                        @click="retryDelivery(d)"
                                        type="button"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700"
                                    >
                                        Kirim Ulang
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    webhook: Object,
    deliveries: Object,
});

const form = useForm({
    url: props.webhook?.url || '',
    secret: props.webhook?.secret || '',
});

const saveWebhook = () => {
    form.post(route('customer.webhooks.store'));
};

const sendTestPing = () => {
    router.post(route('customer.webhooks.test-ping'));
};

const retryDelivery = (delivery) => {
    router.post(route('customer.webhooks.retry', delivery.id));
};
</script>
