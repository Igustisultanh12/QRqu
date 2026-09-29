<template>
    <CustomerLayout>
        <template #header>Detail Transaksi #{{ transaction.id }}</template>

        <div class="space-y-6 max-w-4xl">
            <!-- Header Card -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-colors duration-200">
                <div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Nominal Transaksi</span>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Rp {{ Number(transaction.amount).toLocaleString('id-ID') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-mono">Invoice ID: {{ transaction.invoice_id }} • External ID: {{ transaction.external_id }}</p>
                </div>
                <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-2.5 w-full sm:w-auto">
                    <span
                        :class="[
                            'px-4 py-1.5 rounded-full text-xs font-bold uppercase text-center',
                            transaction.status === 'PAID' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                            transaction.status === 'PENDING' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                            'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                        ]"
                    >
                        {{ transaction.status }}
                    </span>

                    <!-- Tombol Sinkron Status Langsung ke Server Gateway -->
                    <button
                        v-if="transaction.status === 'PENDING'"
                        @click="syncDokuStatus"
                        :disabled="isSyncing"
                        type="button"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 shadow-sm"
                        title="Cek langsung status pembayaran ke server gateway"
                    >
                        <svg v-if="isSyncing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>{{ isSyncing ? 'Mengecek...' : '🔄 Sinkron Status Gateway' }}</span>
                    </button>

                    <!-- Prioritas Utama: Buka Halaman Resmi jika URL tersedia -->
                    <a
                        v-if="dokuCheckoutUrl"
                        :href="dokuCheckoutUrl"
                        target="_blank"
                        class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 active:scale-95 text-white text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 shadow-sm"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>🔴 Buka Portal Pembayaran ↗</span>
                    </a>

                    <button
                        v-if="transaction.status === 'PENDING'"
                        @click="simulatePayment"
                        :disabled="isSimulating"
                        type="button"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 shadow-sm"
                    >
                        <svg v-if="isSimulating" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ isSimulating ? 'Memproses...' : '⚡ Simulasi Lunas' }}</span>
                    </button>

                    <a
                        :href="route('checkout.show', transaction.invoice_id)"
                        target="_blank"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition border border-slate-200 dark:border-slate-700 flex items-center justify-center space-x-1.5"
                    >
                        <span>Checkout QRqu ↗</span>
                    </a>
                </div>
            </div>

            <!-- Pending Simulation Helper Banner -->
            <div v-if="transaction.status === 'PENDING'" class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/50 text-xs text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="font-bold text-amber-800 dark:text-amber-300">Status Transaksi Menunggu Pembayaran (PENDING):</span>
                        <p class="text-[11px] text-amber-700 dark:text-amber-300/90 mt-0.5 leading-relaxed">
                            Pelanggan belum scan atau mentransfer dana QRIS. Jika Anda sudah melakukan pembayaran tapi status belum update, klik <strong>"Cek Status Gateway"</strong>. Untuk keperluan simulasi testing, klik <strong>"⚡ Lunaskan"</strong>.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
                    <button
                        @click="syncDokuStatus"
                        :disabled="isSyncing"
                        type="button"
                        class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition shadow-sm text-center flex items-center justify-center gap-1.5"
                    >
                        <svg v-if="isSyncing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>{{ isSyncing ? 'Mengecek...' : 'Cek Status Gateway' }}</span>
                    </button>
                    <button
                        @click="simulatePayment"
                        :disabled="isSimulating"
                        type="button"
                        class="flex-1 sm:flex-initial px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition shadow-sm text-center"
                    >
                        {{ isSimulating ? 'Memproses...' : '⚡ Lunaskan' }}
                    </button>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Transaction Info -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-colors duration-200">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Informasi Gateway & Transaksi</h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Metode Pembayaran</span>
                            <span class="text-slate-900 dark:text-white font-semibold">QRIS Realtime</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Gateway Reference</span>
                            <span class="font-mono font-semibold">
                                <template v-if="transaction.doku_reference">
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ transaction.doku_reference }}</span>
                                </template>
                                <template v-else-if="transaction.status === 'PENDING'">
                                    <span class="text-amber-500 dark:text-amber-400 font-sans text-[11px] font-normal italic">
                                        ⏳ Belum Terbit (Menunggu Pembayaran)
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-slate-400">N/A</span>
                                </template>
                            </span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Gateway Request ID</span>
                            <span class="text-slate-700 dark:text-slate-300 font-mono truncate max-w-[200px]">{{ transaction.doku_request_id || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Waktu Dibuat</span>
                            <span class="text-slate-700 dark:text-slate-200 font-mono">{{ new Date(transaction.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-500 dark:text-slate-400">Waktu Lunas</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{{ transaction.invoice?.paid_at ? new Date(transaction.invoice.paid_at).toLocaleString('id-ID') : '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-colors duration-200">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Detail Pelanggan & Callback</h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Nama Pembeli</span>
                            <span class="text-slate-900 dark:text-white">{{ transaction.invoice?.customer_name || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Email Pembeli</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ transaction.invoice?.customer_email || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">No. WhatsApp/HP</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ transaction.invoice?.customer_phone || '-' }}</span>
                        </div>
                        <div class="py-2">
                            <span class="text-slate-500 dark:text-slate-400 block mb-1">Webhook URL Merchant</span>
                            <div class="font-mono text-[11px] text-slate-800 dark:text-slate-300 bg-slate-50 dark:bg-slate-900 p-2 rounded-lg border border-slate-200 dark:border-slate-800 break-all">
                                {{ transaction.invoice?.webhook_url || 'Default account webhook' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Status Histories -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-colors duration-200">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Riwayat State Transition (Audit Trail)</h3>
                <div class="space-y-4">
                    <div v-for="hist in transaction.status_histories" :key="hist.id" class="flex items-start space-x-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-slate-900 dark:text-white uppercase">{{ hist.to_status }}</span>
                                <span class="text-slate-400 dark:text-slate-500 font-mono text-[11px]">via {{ hist.trigger }}</span>
                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ new Date(hist.created_at).toLocaleString('id-ID') }}</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 mt-0.5">{{ hist.reason || 'Status updated' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    transaction: Object,
});

const isSimulating = ref(false);
const isSyncing = ref(false);
let pollTimer = null;

const dokuCheckoutUrl = computed(() => {
    const url = props.transaction?.invoice?.qr_url || props.transaction?.doku_transaction?.doku_url;
    if (url && !url.includes('/checkout/' + props.transaction?.invoice_id) && url.startsWith('http')) {
        return url;
    }
    return null;
});

const syncDokuStatus = () => {
    isSyncing.value = true;
    router.post(route('customer.transactions.sync', props.transaction.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSyncing.value = false;
        }
    });
};

const simulatePayment = () => {
    isSimulating.value = true;
    router.post(route('customer.transactions.simulate', props.transaction.id), {}, {
        onFinish: () => {
            isSimulating.value = false;
        }
    });
};

onMounted(() => {
    if (props.transaction?.status === 'PENDING' && props.transaction?.invoice_id) {
        pollTimer = setInterval(async () => {
            try {
                const res = await axios.get(route('checkout.status', props.transaction.invoice_id));
                if (res.data?.is_paid || res.data?.status === 'PAID') {
                    if (pollTimer) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                    }
                    router.reload({ preserveScroll: true });
                }
            } catch (e) {
                // background poll silent catch
            }
        }, 4000);
    }
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});
</script>
