<template>
    <CustomerLayout>
        <template #header>Dashboard Ringkasan Merchant</template>

        <div class="space-y-6">
            <!-- Email Verification Reminder Card -->
            <div
                v-if="!$page.props.auth.user?.has_verified_email"
                class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 p-5 sm:p-6 rounded-3xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm"
            >
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-amber-900 dark:text-amber-200">Anda belum melakukan verifikasi email</h4>
                        <p class="text-xs text-amber-700 dark:text-amber-300/80 mt-0.5">
                            Tautan konfirmasi telah dikirimkan ke <strong>{{ $page.props.auth.user?.email }}</strong>. Silakan verifikasi untuk melindungi akun merchant Anda.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition shadow-sm inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Kirim Ulang Verifikasi</span>
                    </Link>
                </div>
            </div>

            <!-- Active Subscription Alert Card -->
            <div v-if="subscription" class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-emerald-500/30 p-6 sm:p-8 rounded-[2rem] flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                            {{ subscription.status }}
                        </span>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ subscription.plan_name }}</h2>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                        Berlaku sampai: <strong class="text-slate-800 dark:text-slate-200">{{ subscription.expires_at }}</strong> (Sisa <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ subscription.remaining_days }} hari</span>) • Kuota Transaksi: {{ subscription.transaction_limit }} • Limit Kecepatan: {{ subscription.rate_limit_rpm }} RPM
                    </p>
                </div>
                <Link :href="route('customer.subscription.index')" class="px-5 py-2.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs tracking-tight transition shadow-sm active:scale-95 shrink-0">
                    Perpanjang / Upgrade Paket →
                </Link>
            </div>

            <div v-else class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-amber-500/30 p-6 sm:p-8 rounded-[2rem] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Belum Ada Subscription Aktif</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pilih paket langganan untuk mulai menerima transaksi QRIS dan menggunakan API QRqu.</p>
                </div>
                <Link :href="route('customer.subscription.index')" class="px-5 py-2.5 rounded-full bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs tracking-tight transition shadow-lg shadow-orange-500/25 active:scale-95 shrink-0">
                    Pilih Paket Langganan →
                </Link>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Volume -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Volume Transaksi Lunas</span>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-2">{{ kpi.total_volume_formatted }}</div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 block font-medium">✓ Berhasil diselesaikan</span>
                </div>

                <!-- Total Transaksi -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Transaksi</span>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-2">{{ kpi.total_transactions }}</div>
                    <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ kpi.successful_transactions }} Sukses</span>
                        <span>•</span>
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ kpi.pending_transactions }} Pending</span>
                    </div>
                </div>

                <!-- API Today -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">API Request Hari Ini</span>
                    <div class="text-2xl sm:text-3xl font-black text-teal-600 dark:text-teal-300 mt-2">{{ kpi.api_today }}</div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-2 block">Bulan ini: {{ kpi.api_month }} hits</span>
                </div>

                <!-- Failed / Expired -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all hover:shadow-md">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gagal / Expired</span>
                    <div class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 mt-2">{{ kpi.failed_transactions }}</div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-2 block">Invoice batal atau kedaluwarsa</span>
                </div>
            </div>

            <!-- Chart & Visual Summary -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-all">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Aktivitas Transaksi (7 Hari Terakhir)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tren volume dan frekuensi transaksi harian merchant</p>
                    </div>
                </div>

                <!-- CSS / SVG Bar Chart -->
                <div class="h-48 flex items-end justify-between gap-2 pt-6 border-b border-slate-200 dark:border-slate-800">
                    <div v-for="(item, idx) in chart_data" :key="idx" class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
                        <div class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            {{ item.count }} trx
                        </div>
                        <div
                            class="w-full bg-emerald-500/20 group-hover:bg-emerald-500 dark:bg-emerald-500/30 dark:group-hover:bg-emerald-400 rounded-t-lg transition-all"
                            :style="{ height: Math.max(12, (item.count / maxChartCount) * 100) + '%' }"
                        ></div>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium whitespace-nowrap">{{ item.date }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm transition-all">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Transaksi Terkini</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">5 Transaksi terakhir yang masuk ke akun Anda</p>
                    </div>
                    <Link :href="route('customer.transactions.index')" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Lihat Semua Transaksi →
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Transaction ID</th>
                                <th class="p-4">Invoice / External ID</th>
                                <th class="p-4">Nominal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Waktu</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                            <tr v-if="recent_transactions.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    Belum ada transaksi yang tercatat. Gunakan API untuk membuat tagihan pertama Anda!
                                </td>
                            </tr>
                            <tr v-for="trx in recent_transactions" :key="trx.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                                <td class="p-4 font-mono font-bold text-slate-900 dark:text-slate-200">{{ trx.id }}</td>
                                <td class="p-4">
                                    <div class="font-mono text-slate-800 dark:text-slate-300">{{ trx.invoice_id }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ trx.external_id }}</div>
                                </td>
                                <td class="p-4 font-bold text-slate-900 dark:text-white">
                                    Rp {{ Number(trx.amount).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase',
                                            trx.status === 'PAID' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                            trx.status === 'PENDING' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                                            'bg-rose-50 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ trx.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                    {{ new Date(trx.created_at).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4 text-right">
                                    <Link :href="route('customer.transactions.show', trx.id)" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold">
                                        Detail
                                    </Link>
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
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    subscription: Object,
    kpi: Object,
    recent_transactions: Array,
    chart_data: Array,
});

const maxChartCount = computed(() => {
    const counts = props.chart_data?.map(i => i.count) || [1];
    return Math.max(...counts, 1);
});
</script>
