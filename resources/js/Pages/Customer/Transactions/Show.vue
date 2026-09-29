<template>
    <CustomerLayout>
        <template #header>Detail Transaksi #{{ transaction.id }}</template>

        <div class="space-y-6 max-w-4xl">
            <!-- Header Card -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold">Nominal Transaksi</span>
                    <h2 class="text-3xl font-black text-white mt-1">
                        Rp {{ Number(transaction.amount).toLocaleString('id-ID') }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 font-mono">Invoice ID: {{ transaction.invoice_id }} • External ID: {{ transaction.external_id }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span
                        :class="[
                            'px-4 py-1.5 rounded-full text-xs font-bold uppercase',
                            transaction.status === 'PAID' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                            transaction.status === 'PENDING' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
                            'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                        ]"
                    >
                        {{ transaction.status }}
                    </span>
                    <a :href="route('checkout.show', transaction.invoice_id)" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition border border-slate-700">
                        Buka Halaman Checkout ↗
                    </a>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Transaction Info -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Informasi DOKU & Gateway</h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">Metode Pembayaran</span>
                            <span class="text-white font-semibold">QRIS Realtime</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">DOKU Reference</span>
                            <span class="text-emerald-400 font-mono font-semibold">{{ transaction.doku_reference || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">DOKU Request ID</span>
                            <span class="text-slate-300 font-mono truncate max-w-[200px]">{{ transaction.doku_request_id || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">Waktu Dibuat</span>
                            <span class="text-slate-200 font-mono">{{ new Date(transaction.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-slate-400">Waktu Lunas</span>
                            <span class="text-emerald-400 font-mono font-bold">{{ transaction.invoice?.paid_at ? new Date(transaction.invoice.paid_at).toLocaleString('id-ID') : '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Detail Pelanggan & Callback</h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">Nama Pembeli</span>
                            <span class="text-white">{{ transaction.invoice?.customer_name || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">Email Pembeli</span>
                            <span class="text-slate-300">{{ transaction.invoice?.customer_email || '-' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800/80">
                            <span class="text-slate-400">No. WhatsApp/HP</span>
                            <span class="text-slate-300">{{ transaction.invoice?.customer_phone || '-' }}</span>
                        </div>
                        <div class="py-2">
                            <span class="text-slate-400 block mb-1">Webhook URL Merchant</span>
                            <div class="font-mono text-[11px] text-slate-300 bg-slate-900 p-2 rounded-lg break-all">
                                {{ transaction.invoice?.webhook_url || 'Default account webhook' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Status Histories -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Riwayat State Transition (Audit Trail)</h3>
                <div class="space-y-4">
                    <div v-for="hist in transaction.status_histories" :key="hist.id" class="flex items-start space-x-3 text-xs">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5 shrink-0"></div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-white uppercase">{{ hist.to_status }}</span>
                                <span class="text-slate-500 font-mono text-[11px]">via {{ hist.trigger }}</span>
                                <span class="text-slate-500">•</span>
                                <span class="text-slate-400 font-mono text-[11px]">{{ new Date(hist.created_at).toLocaleString('id-ID') }}</span>
                            </div>
                            <p class="text-slate-400 mt-0.5">{{ hist.reason || 'Status updated' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    transaction: Object,
});
</script>
