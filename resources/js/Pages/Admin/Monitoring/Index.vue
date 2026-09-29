<template>
    <AdminLayout>
        <template #header>Monitoring Gateway & Live Testing</template>

        <div class="space-y-6 max-w-5xl">
            <!-- Heading (Romei Style) -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Monitoring & Uji Coba Gateway</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Pantau kestabilan koneksi DOKU, status worker webhook, dan lakukan uji transaksi QRIS secara langsung.
                </p>
            </div>

            <!-- Health Cards (Romei Style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Database -->
                <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Database SQL</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.database ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ health.database ? 'Operational' : 'Disconnected' }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">Koneksi SQL aktif normal</span>
                </div>

                <!-- Cache & Redis -->
                <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cache & Memory</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.cache ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ health.cache ? 'Operational' : 'Unavailable' }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">Memory lock & caching ready</span>
                </div>

                <!-- DOKU Payment API -->
                <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">DOKU Payment Gateway</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.doku_api ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ health.doku_api ? 'Connected' : 'Simulation Mode' }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">API endpoint & signature live</span>
                </div>

                <!-- PHP Runtime -->
                <div class="bg-white dark:bg-slate-950 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">PHP & Engine</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2 font-mono">PHP {{ health.php_version }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">Memory: {{ health.memory_usage }}</span>
                </div>
            </div>

            <!-- DOKU QRIS GATEWAY LIVE TEST CARD (Exact Romei Background Card) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- QR Code Gateway Live Test (Uji Coba Transaksi) -->
                <div class="bg-white dark:bg-slate-950 p-6 sm:p-7 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5 transition-colors">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">QR Code Gateway Live Test</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Uji langsung generate QRIS DOKU real-time</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-md bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 font-semibold text-[11px] border border-emerald-200 dark:border-emerald-500/20">
                            Protokol Romei
                        </span>
                    </div>

                    <form @submit.prevent="generateTestQris" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                Nominal Pembayaran (Rp)
                            </label>
                            <input
                                v-model.number="testAmount"
                                type="number"
                                required
                                min="1000"
                                step="100"
                                placeholder="Contoh: 1000"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-sm font-black font-mono text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>

                        <!-- Preset Quick Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="amt in [1000, 2000, 5000, 10000]"
                                :key="amt"
                                type="button"
                                @click="testAmount = amt"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-semibold transition border',
                                    testAmount === amt
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                                ]"
                            >
                                Rp {{ amt.toLocaleString('id-ID') }}
                            </button>
                        </div>

                        <div v-if="testError" class="p-3 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-xl text-xs text-rose-700 dark:text-rose-300">
                            {{ testError }}
                        </div>

                        <button
                            type="submit"
                            :disabled="generating"
                            class="w-full py-3 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-md shadow-indigo-600/20 flex items-center justify-center space-x-2"
                        >
                            <svg v-if="generating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>{{ generating ? 'Menghubungi DOKU...' : 'Test Pembayaran & Tampilkan QRIS' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Webhook Inbound Payload Card (Romei Style Right Card) -->
                <div class="bg-white dark:bg-slate-950 p-6 sm:p-7 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Status Worker & Queue Webhook</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Monitoring pengiriman notifikasi transaksi</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-center">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold">Pending</span>
                            <div class="text-xl font-black text-amber-500 mt-1">{{ health.queue_status?.pending_webhooks || 0 }}</div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-center">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold">Retrying</span>
                            <div class="text-xl font-black text-indigo-500 mt-1">{{ health.queue_status?.retrying_webhooks || 0 }}</div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-center">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold">Failed</span>
                            <div class="text-xl font-black text-rose-500 mt-1">{{ health.queue_status?.failed_webhooks || 0 }}</div>
                        </div>
                    </div>

                    <div class="pt-2 text-xs text-slate-500 dark:text-slate-400 space-y-2">
                        <p class="leading-relaxed">
                            Setiap pembayaran QRIS yang diselesaikan akan memicu webhook HTTP POST otomatis ke URL merchant dengan header tanda tangan <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">X-QRQU-Signature</code>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Health Endpoints -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3 transition-colors">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Internal Health Probes</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
                    <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400">GET /health</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">HTTP 200 (Liveness)</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400">GET /ready</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">HTTP 200 (Readiness)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Romei Exact QRIS Payment Modal Component -->
        <QrisPaymentModal
            :show="showModal"
            :invoice-id="modalData.invoiceId"
            :amount="modalData.amount"
            :qr-string="modalData.qrString"
            :qr-url="modalData.qrUrl"
            :nmid="modalData.nmid"
            :checkout-url="modalData.checkoutUrl"
            :status="modalData.status"
            @close="showModal = false"
            @status-updated="onStatusUpdated"
        />
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QrisPaymentModal from '@/Components/QrisPaymentModal.vue';

defineProps({
    health: Object,
});

const testAmount = ref(1000);
const generating = ref(false);
const testError = ref(null);
const showModal = ref(false);

const modalData = ref({
    invoiceId: '',
    amount: 1000,
    qrString: '',
    qrUrl: '',
    nmid: 'ID1026478551298',
    checkoutUrl: '',
    status: 'PENDING',
});

const generateTestQris = async () => {
    generating.value = true;
    testError.value = null;

    try {
        const response = await axios.post(route('admin.settings.test-payment'), {
            amount: testAmount.value,
            customer_name: 'Admin Tester',
        });

        if (response.data.success) {
            modalData.value = {
                invoiceId: response.data.invoice_id,
                amount: response.data.amount,
                qrString: response.data.qr_string,
                qrUrl: response.data.qr_url,
                nmid: response.data.nmid || 'ID1026478551298',
                checkoutUrl: response.data.checkout_url,
                status: response.data.status || 'PENDING',
            };
            showModal.value = true;
        } else {
            testError.value = response.data.message || 'Gagal membuat QRIS di DOKU.';
        }
    } catch (err) {
        testError.value = err.response?.data?.message || err.message || 'Gagal memanggil API DOKU.';
    } finally {
        generating.value = false;
    }
};

const onStatusUpdated = (newStatus) => {
    modalData.value.status = newStatus;
};
</script>
