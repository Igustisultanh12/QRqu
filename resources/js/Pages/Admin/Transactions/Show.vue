<template>
    <AdminLayout>
        <template #header>Master Detail Transaksi: {{ transaction.id }}</template>

        <div class="space-y-6 max-w-5xl">
            <!-- Header & Action -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-colors duration-200">
                <div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Transaksi Gateway</span>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Rp {{ Number(transaction.amount).toLocaleString('id-ID') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Merchant: <strong class="text-slate-900 dark:text-white">{{ transaction.customer?.company_name || transaction.customer?.name }}</strong> ({{ transaction.customer?.email }})
                    </p>
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

                    <!-- Link Langsung ke Portal DOKU Resmi -->
                    <a
                        v-if="dokuCheckoutUrl"
                        :href="dokuCheckoutUrl"
                        target="_blank"
                        class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>🔴 Buka Portal DOKU ↗</span>
                    </a>

                    <a
                        :href="route('checkout.show', transaction.invoice_id)"
                        target="_blank"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition border border-slate-200 dark:border-slate-700 flex items-center justify-center space-x-1.5"
                    >
                        <span>Checkout QRqu ↗</span>
                    </a>

                    <button
                        v-if="['CREATED', 'PENDING'].includes(transaction.status)"
                        @click="simulatePayment"
                        :disabled="isSimulating"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm"
                    >
                        <svg v-if="isSimulating" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ isSimulating ? 'Memproses...' : '⚡ Simulasi Lunas & Kirim Webhook' }}</span>
                    </button>

                    <button
                        v-if="['CREATED', 'PENDING'].includes(transaction.status)"
                        @click="cancelTrx"
                        class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/20 dark:hover:bg-rose-500/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30 rounded-xl text-xs font-semibold transition text-center"
                    >
                        Batalkan Transaksi
                    </button>
                </div>
            </div>

            <!-- Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3 transition-colors duration-200">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Parameter DOKU</h3>
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400">Invoice ID</span>
                        <span class="text-slate-900 dark:text-white font-mono">{{ transaction.invoice_id }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400">External ID Merchant</span>
                        <span class="text-slate-900 dark:text-white font-mono">{{ transaction.external_id }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400">DOKU Reference</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{{ transaction.doku_reference || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 dark:text-slate-400">DOKU Request UUID</span>
                        <span class="text-slate-700 dark:text-slate-300 font-mono truncate max-w-[200px]">{{ transaction.doku_request_id || '-' }}</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3 transition-colors duration-200">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Detail Invoice</h3>
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400">Nama Pembeli</span>
                        <span class="text-slate-900 dark:text-white">{{ transaction.invoice?.customer_name || '-' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400">Email Pembeli</span>
                        <span class="text-slate-900 dark:text-white">{{ transaction.invoice?.customer_email || '-' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                        <span class="text-slate-500 dark:text-slate-400">Expired At</span>
                        <span class="text-slate-700 dark:text-slate-300 font-mono">{{ transaction.invoice?.expired_at ? new Date(transaction.invoice.expired_at).toLocaleString('id-ID') : '-' }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-500 dark:text-slate-400">Paid At</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold font-mono">{{ transaction.invoice?.paid_at ? new Date(transaction.invoice.paid_at).toLocaleString('id-ID') : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- State Transition Audit History -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-colors duration-200">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">State Transition Log</h3>
                <div class="space-y-3 text-xs">
                    <div v-for="h in transaction.status_histories" :key="h.id" class="flex items-start space-x-3">
                        <div class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 shrink-0"></div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-slate-900 dark:text-white uppercase">{{ h.to_status }}</span>
                                <span class="text-slate-400 dark:text-slate-500 font-mono text-[11px]">trigger: {{ h.trigger }}</span>
                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ new Date(h.created_at).toLocaleString('id-ID') }}</span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 mt-0.5">{{ h.reason }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    transaction: Object,
});

const isSimulating = ref(false);

const dokuCheckoutUrl = computed(() => {
    const url = props.transaction?.invoice?.qr_url || props.transaction?.doku_transaction?.doku_url;
    if (url && !url.includes('/checkout/' + props.transaction?.invoice_id) && url.startsWith('http')) {
        return url;
    }
    return null;
});

const cancelTrx = () => {
    if (confirm('Batalkan transaksi ini? Invoice dan QRIS tidak akan dapat dibayar lagi.')) {
        router.post(route('admin.transactions.cancel', props.transaction.id));
    }
};

const simulatePayment = () => {
    isSimulating.value = true;
    router.post(route('admin.transactions.simulate', props.transaction.id), {}, {
        onFinish: () => {
            isSimulating.value = false;
        }
    });
};
</script>
