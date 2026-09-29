<template>
    <AdminLayout>
        <template #header>Platform Overview & Master Analytics</template>

        <div class="space-y-6">
            <!-- Platform KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Subscription Revenue -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pendapatan Subscription QRqu</span>
                    <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-2">{{ metrics.subscription_revenue_formatted }}</div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-2 block">Revenue murni biaya langganan paket</span>
                </div>

                <!-- Gross Transaction Volume -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Transaction Value (GTV)</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2">{{ metrics.gross_transaction_value_formatted }}</div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-2 block">Total perputaran transaksi gateway</span>
                </div>

                <!-- Total Customers -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Pelanggan / Merchant</span>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-2">{{ metrics.total_customers }}</div>
                    <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ metrics.active_customers }} Aktif</span>
                        <span>•</span>
                        <span class="text-rose-600 dark:text-rose-400 font-semibold">{{ metrics.suspended_customers }} Ditangguhkan</span>
                    </div>
                </div>

                <!-- Webhook Success Rate -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Webhook Success Rate</span>
                    <div class="text-2xl sm:text-3xl font-black text-teal-600 dark:text-teal-300 mt-2">{{ metrics.webhook_success_rate }}%</div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-2 block">Gagal: {{ metrics.webhook_failed_count }} tembakan</span>
                </div>
            </div>

            <!-- Volume & Transactions Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Transaksi Hari Ini</span>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">{{ metrics.today_transactions }} transaksi</div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1 block">{{ metrics.today_volume_formatted }}</span>
                </div>

                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Transaksi Selesai</span>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">{{ metrics.successful_transactions }} Transaksi Lunas</div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 block">Dari total {{ metrics.total_transactions }} invoice</span>
                </div>

                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending & Gagal</span>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">{{ metrics.pending_transactions }} Pending</div>
                    <span class="text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1 block">{{ metrics.failed_transactions }} Gagal / Expired</span>
                </div>
            </div>

            <!-- Visual Chart -->
            <div class="bg-white dark:bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tren Transaksi Platform (14 Hari Terakhir)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total volume dan jumlah invoice QRIS yang diproses sistem</p>
                </div>

                <div class="h-48 flex items-end justify-between gap-2 pt-6 border-b border-slate-200 dark:border-slate-800">
                    <div v-for="(item, idx) in chart_data" :key="idx" class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
                        <div class="text-[10px] font-mono font-bold text-indigo-600 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            {{ item.count }} trx
                        </div>
                        <div
                            class="w-full bg-indigo-500/20 group-hover:bg-indigo-500 dark:bg-indigo-500/30 dark:group-hover:bg-indigo-400 rounded-t-lg transition-all"
                            :style="{ height: Math.max(12, (item.count / maxChartCount) * 100) + '%' }"
                        ></div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono whitespace-nowrap">{{ item.date }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Master Transactions -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm transition-all">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Transaksi Sistem Terbaru</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Arus pembayaran yang masuk ke gateway dari semua tenant</p>
                    </div>
                    <Link :href="route('admin.transactions.index')" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Buka Master Transaksi →
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Transaction ID</th>
                                <th class="p-4">Customer / Tenant</th>
                                <th class="p-4">Nominal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Ref Transaksi</th>
                                <th class="p-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                            <tr v-for="trx in recent_transactions" :key="trx.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-slate-900 dark:text-white">{{ trx.id }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-slate-200">{{ trx.customer?.company_name || trx.customer?.name }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ trx.customer?.email }}</div>
                                </td>
                                <td class="p-4 font-bold text-emerald-600 dark:text-emerald-400">
                                    Rp {{ Number(trx.amount).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            trx.status === 'PAID' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                            trx.status === 'PENDING' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                                            'bg-rose-50 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ trx.status }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ trx.doku_reference || '-' }}</td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ new Date(trx.created_at).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    metrics: Object,
    chart_data: Array,
    recent_transactions: Array,
});

const maxChartCount = computed(() => {
    const counts = props.chart_data?.map(i => i.count) || [1];
    return Math.max(...counts, 1);
});
</script>
