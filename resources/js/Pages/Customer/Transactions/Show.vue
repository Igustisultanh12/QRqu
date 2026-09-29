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
                <div class="flex flex-wrap items-center gap-2.5">
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
                        v-if="transaction.status === 'PENDING'"
                        @click="simulatePayment"
                        :disabled="isSimulating"
                        type="button"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center space-x-1.5 shadow-sm"
                    >
                        <svg v-if="isSimulating" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ isSimulating ? 'Memproses...' : '⚡ Simulasi Bayar Lunas (Testing)' }}</span>
                    </button>

                    <a :href="route('checkout.show', transaction.invoice_id)" target="_blank" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl transition border border-slate-200 dark:border-slate-700">
                        Buka Halaman Checkout ↗
                    </a>
                </div>
            </div>

            <!-- Pending Simulation Helper Banner -->
            <div v-if="transaction.status === 'PENDING'" class="p-4 rounded-3xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/50 text-xs text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="font-bold text-amber-800 dark:text-amber-300">Status Transaksi Menunggu Pembayaran (PENDING):</span>
                        <p class="text-[11px] text-amber-700 dark:text-amber-300/90 mt-0.5 leading-relaxed">
                            Pelanggan belum scan atau mentransfer dana QRIS. Jika Anda sedang mengetes alur checkout Romei, klik tombol <strong>"Simulasi Bayar Lunas"</strong> untuk otomatis mengubah status menjadi <strong>PAID</strong> dan menembakkan notifikasi webhook lunas ke platform Romei.
                        </p>
                    </div>
                </div>
                <button
                    @click="simulatePayment"
                    :disabled="isSimulating"
                    type="button"
                    class="shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition shadow-sm"
                >
                    {{ isSimulating ? 'Memproses...' : '⚡ Lunaskan Sekarang' }}
                </button>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Transaction Info -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-colors duration-200">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Informasi DOKU & Gateway</h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">Metode Pembayaran</span>
                            <span class="text-slate-900 dark:text-white font-semibold">QRIS Realtime</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">DOKU Reference</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-mono font-semibold">{{ transaction.doku_reference || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800/80">
                            <span class="text-slate-500 dark:text-slate-400">DOKU Request ID</span>
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    transaction: Object,
});

const isSimulating = ref(false);

const simulatePayment = () => {
    isSimulating.value = true;
    router.post(route('customer.transactions.simulate', props.transaction.id), {}, {
        onFinish: () => {
            isSimulating.value = false;
        }
    });
};
</script>
