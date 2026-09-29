<template>
    <AdminLayout>
        <template #header>Monitoring Gateway & Live Testing</template>

        <div class="space-y-6 max-w-5xl relative">
            <!-- Toast Notification (Romei Style) -->
            <div
                v-if="toastNotification"
                class="fixed top-6 right-6 z-50 max-w-sm w-full border rounded-2xl shadow-2xl p-4 transition-all duration-300 backdrop-blur-md"
                :class="[
                    toastNotification.status === 'success' ? 'bg-emerald-600 border-emerald-500 text-white' : '',
                    toastNotification.status === 'error' ? 'bg-rose-600 border-rose-500 text-white' : '',
                    toastNotification.status === 'info' ? 'bg-indigo-600 border-indigo-500 text-white' : ''
                ]"
            >
                <div class="flex items-start gap-3">
                    <svg v-if="toastNotification.status === 'success'" class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else-if="toastNotification.status === 'error'" class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <svg v-else class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-black uppercase tracking-wider">{{ toastNotification.title }}</h4>
                        <p class="text-[11px] font-medium opacity-95 leading-relaxed font-mono text-left">{{ toastNotification.message }}</p>
                    </div>
                </div>
            </div>

            <!-- Heading -->
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Monitoring & Uji Coba Gateway</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Pantau status koneksi Gateway, antrean worker webhook, dan uji coba pembayaran QRIS secara live produksi.
                </p>
            </div>

            <!-- Health Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Database -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Database SQL</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.database ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ health.database ? 'Operational' : 'Disconnected' }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">Koneksi SQL aktif normal</span>
                </div>

                <!-- Cache & Memory -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cache & Memory</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.cache ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ health.cache ? 'Operational' : 'Unavailable' }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">Memory lock & caching ready</span>
                </div>

                <!-- Payment API -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Payment Gateway</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.doku_api ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ health.doku_api ? 'Connected' : 'Simulation Mode' }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">API endpoint & signature ready</span>
                </div>

                <!-- PHP Runtime -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">PHP & Engine</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white mt-2 font-mono">PHP {{ health.php_version }}</div>
                    <span class="text-[11px] text-slate-500 mt-0.5 block">Memory: {{ health.memory_usage }}</span>
                </div>
            </div>

            <!-- GATEWAY TEST & WEBHOOK MONITORING -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Uji Coba Gate Transaksi (Production Page) -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 sm:p-7 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-5 transition-colors">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Uji Coba Gate Transaksi Gateway</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tembak langsung request pembuatan invoice live ke server gateway untuk memastikan keabsahan *Signature* komersial.</p>
                    </div>

                    <form @submit.prevent="runPaymentSimulation" class="space-y-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Nominal Transaksi Asli (IDR)
                            </label>
                            <input
                                v-model.number="txAmount"
                                type="number"
                                required
                                min="1"
                                step="1"
                                placeholder="Mulai dari Rp 1"
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold px-3.5 py-2.5 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <!-- Preset Quick Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="amt in [1, 1000, 2000, 5000, 10000]"
                                :key="amt"
                                type="button"
                                @click="txAmount = amt"
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-semibold transition border',
                                    txAmount === amt
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                                ]"
                            >
                                Rp {{ amt.toLocaleString('id-ID') }}
                            </button>
                        </div>

                        <!-- Metode Transaksi -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Metode Transaksi</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="txPaymentMethod = 'qris'"
                                    :class="txPaymentMethod === 'qris' ? 'border-indigo-500 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-400 hover:text-slate-300'"
                                    class="border rounded-xl p-3 text-center flex flex-col items-center gap-1.5 transition-all"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                    <span class="text-[10px] uppercase">QRIS Live</span>
                                </button>
                                <button
                                    type="button"
                                    @click="txPaymentMethod = 'shopeepay'"
                                    :class="txPaymentMethod === 'shopeepay' ? 'border-orange-500 bg-orange-500/10 text-orange-500 font-bold' : 'border-slate-200 dark:border-slate-800 text-slate-400 hover:text-slate-300'"
                                    class="border rounded-xl p-3 text-center flex flex-col items-center gap-1.5 transition-all"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span class="text-[10px] uppercase">ShopeePay</span>
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="isSimulating"
                            class="w-full py-3 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs transition shadow-md shadow-indigo-600/20 flex items-center justify-center space-x-2"
                        >
                            <svg v-if="isSimulating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>{{ isSimulating ? 'Membuka Jendela DOKU Live...' : 'Tembak Transaksi Produksi' }}</span>
                        </button>
                    </form>
                </div>

                <!-- Webhook Inbound Status (Romei Style Right Card) -->
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

        <!-- Romei Authentic DOKU Live Iframe Modal Component -->
        <QrisPaymentModal
            :show="showDokuModal"
            :invoice-id="currentInvoiceId"
            :amount="txAmount"
            :payment-url="activePaymentUrl"
            :qr-url="activePaymentUrl"
            :status="currentStatus"
            @close="closeDokuModalManual"
            @status-updated="onStatusUpdated"
            @paid="onPaid"
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

const txAmount = ref(1000);
const txPaymentMethod = ref('qris');
const isSimulating = ref(false);

const showDokuModal = ref(false);
const activePaymentUrl = ref('');
const currentInvoiceId = ref('');
const currentStatus = ref('PENDING');

const toastNotification = ref(null);

const showToast = (status, title, message) => {
    toastNotification.value = { status, title, message };
    setTimeout(() => {
        toastNotification.value = null;
    }, 6000);
};

const runPaymentSimulation = () => {
    isSimulating.value = true;
    toastNotification.value = null;

    axios.post('/api/admin/monitoring/test-payment', {
        amount: parseInt(txAmount.value),
        payment_method: txPaymentMethod.value,
    })
    .then((response) => {
        const paymentUrl = response.data.payment_url || response.data.qr_url;
        if ((response.data.status === 'success' || response.data.success) && paymentUrl) {
            currentInvoiceId.value = response.data.invoice_id;
            activePaymentUrl.value = paymentUrl;
            currentStatus.value = 'PENDING';
            showDokuModal.value = true;

            showToast('info', 'Portal Terbuka', 'Silakan lakukan scan / penyelesaian pembayaran pada popup internal.');
        } else {
            showToast('error', 'Gateway Error', response.data.message || 'Gagal memuat URL halaman pembayaran dari respon DOKU.');
        }
    })
    .catch((error) => {
        const errorMsg = error.response?.data?.message || 'DOKU Live Gateway menolak payload mas. Periksa parameter signature.';
        showToast('error', 'Transaksi Ditolak', errorMsg);
    })
    .finally(() => {
        isSimulating.value = false;
    });
};

const closeDokuModalManual = () => {
    showDokuModal.value = false;
    activePaymentUrl.value = '';
    showToast('info', 'Popup Ditutup', 'Pengujian portal pembayaran dihentikan oleh admin.');
};

const onStatusUpdated = (newStatus) => {
    currentStatus.value = newStatus;
};

const onPaid = (invoiceId) => {
    showDokuModal.value = false;
    activePaymentUrl.value = '';
    showToast(
        'success',
        'Pembayaran Berhasil',
        `Transaksi ${invoiceId} telah sukses dibayar! Sistem otomatis memperbarui data invoice.`
    );
};
</script>
