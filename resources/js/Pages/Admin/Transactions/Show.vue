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

                <div class="flex items-center space-x-3">
                    <span
                        :class="[
                            'px-4 py-1.5 rounded-full text-xs font-bold uppercase',
                            transaction.status === 'PAID' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                            transaction.status === 'PENDING' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                            'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                        ]"
                    >
                        {{ transaction.status }}
                    </span>

                    <button
                        v-if="['CREATED', 'PENDING'].includes(transaction.status)"
                        @click="cancelTrx"
                        class="px-4 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/20 dark:hover:bg-rose-500/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30 rounded-xl text-xs font-semibold transition"
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
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    transaction: Object,
});

const cancelTrx = () => {
    if (confirm('Batalkan transaksi ini? Invoice dan QRIS tidak akan dapat dibayar lagi.')) {
        router.post(route('admin.transactions.cancel', props.transaction.id));
    }
};
</script>
