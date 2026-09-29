<template>
    <CustomerLayout>
        <template #header>Laporan Bulanan Transaksi</template>

        <div class="space-y-6">
            <!-- Header Filter Periode & Ekspor -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Laporan Periode {{ period.label }}</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 font-normal">
                            {{ quota.plan_name }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Analisis transaksi berhasil vs gagal, rekapan nama transaksi, dan kuota bulanan merchant.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Selector Bulan -->
                    <select
                        v-model="selectedMonth"
                        @change="applyFilter"
                        class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                        <option v-for="(name, mIndex) in monthNames" :key="mIndex + 1" :value="mIndex + 1">
                            {{ name }}
                        </option>
                    </select>

                    <!-- Selector Tahun -->
                    <select
                        v-model="selectedYear"
                        @change="applyFilter"
                        class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500"
                    >
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">
                            {{ y }}
                        </option>
                    </select>

                    <!-- Tombol Download CSV -->
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition"
                    >
                        <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Ekspor CSV
                    </a>

                    <!-- Tombol Cetak / Print -->
                    <button
                        @click="printReport"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak Laporan
                    </button>
                </div>
            </div>

            <!-- Card Kuota Bulanan Terpakai -->
            <div class="bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:to-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pemantauan Kuota Bulanan</span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                            {{ Number(quota.used).toLocaleString('id-ID') }}
                            <span class="text-sm font-normal text-slate-500 dark:text-slate-400">/ {{ Number(quota.limit).toLocaleString('id-ID') }} transaksi</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Sisa kuota: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ Number(quota.remaining).toLocaleString('id-ID') }} transaksi</span> lagi untuk bulan ini.
                        </p>
                    </div>

                    <div class="text-left md:text-right">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Biaya Paket Bulanan</span>
                        <div class="text-lg font-black text-slate-900 dark:text-white">Rp {{ Number(quota.monthly_price).toLocaleString('id-ID') }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/ bln</span></div>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Masa aktif s/d: {{ quota.expires_at }}</span>
                    </div>
                </div>

                <!-- Progress Bar Kuota -->
                <div class="mt-4">
                    <div class="w-full bg-slate-100 dark:bg-slate-800/80 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/50">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="[
                                quota.percentage > 90 ? 'bg-rose-500' : (quota.percentage > 70 ? 'bg-amber-500' : 'bg-emerald-500')
                            ]"
                            :style="{ width: quota.percentage + '%' }"
                        ></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 font-mono">
                        <span>0%</span>
                        <span :class="quota.percentage > 90 ? 'text-rose-500 dark:text-rose-400 font-bold' : 'text-slate-600 dark:text-slate-300 font-semibold'">{{ quota.percentage }}% Terpakai</span>
                        <span>100% (Maks: {{ Number(quota.limit).toLocaleString('id-ID') }})</span>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Statistik 4 Kolom: Berhasil vs Gagal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Transaksi Berhasil -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Transaksi Berhasil</span>
                        <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                        {{ Number(summary.successful_count).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">
                        Rp {{ Number(summary.successful_amount).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Status: PAID / SUKSES</div>
                </div>

                <!-- Transaksi Gagal -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Transaksi Gagal</span>
                        <span class="p-2 rounded-xl bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-2">
                        {{ Number(summary.failed_count).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">
                        Rp {{ Number(summary.failed_amount).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Status: FAILED / CANCEL</div>
                </div>

                <!-- Transaksi Kedaluwarsa -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kedaluwarsa (Expired)</span>
                        <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">
                        {{ Number(summary.expired_count).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">
                        Rp {{ Number(summary.expired_amount).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Status: EXPIRED</div>
                </div>

                <!-- Rasio Sukses & Total Transaksi -->
                <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tingkat Keberhasilan</span>
                        <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-2">
                        {{ summary.success_rate }}%
                    </div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">
                        Total {{ Number(summary.total_count).toLocaleString('id-ID') }} Transaksi
                    </div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Vol: Rp {{ Number(summary.total_amount).toLocaleString('id-ID') }}</div>
                </div>
            </div>

            <!-- Tabel Daftar Rincian Transaksi Bulanan -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <!-- Filter & Search Bar -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50 dark:bg-transparent">
                    <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
                        <button
                            v-for="st in [
                                { label: 'Semua', val: 'all' },
                                { label: 'Berhasil (PAID)', val: 'PAID' },
                                { label: 'Gagal (FAILED)', val: 'FAILED' },
                                { label: 'Kedaluwarsa', val: 'EXPIRED' },
                                { label: 'Menunggu', val: 'PENDING' }
                            ]"
                            :key="st.val"
                            @click="filterStatus(st.val)"
                            :class="[
                                'px-3 py-1.5 rounded-xl text-xs font-semibold transition',
                                currentStatus === st.val
                                    ? 'bg-emerald-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'
                            ]"
                        >
                            {{ st.label }}
                        </button>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilter"
                            type="text"
                            placeholder="Cari nama transaksi / ID..."
                            class="w-full pl-9 pr-3.5 py-1.5 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 rounded-xl text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                        />
                        <svg class="w-4 h-4 absolute left-3 top-2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Tanggal & Jam</th>
                                <th class="py-3 px-4 font-semibold">Nama Transaksi</th>
                                <th class="py-3 px-4 font-semibold">No. Invoice & ID</th>
                                <th class="py-3 px-4 font-semibold">Pelanggan</th>
                                <th class="py-3 px-4 font-semibold text-right">Nominal</th>
                                <th class="py-3 px-4 font-semibold text-center">Status</th>
                                <th class="py-3 px-4 font-semibold">Ref Gateway</th>
                                <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ trx.created_at }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white max-w-xs truncate" :title="trx.nama_transaksi">
                                        {{ trx.nama_transaksi }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">Ext: {{ trx.external_id }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    <div class="text-slate-800 dark:text-slate-300">{{ trx.invoice_id }}</div>
                                    <div class="text-slate-400 dark:text-slate-500 text-[10px]">{{ trx.id }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-800 dark:text-slate-200 font-semibold">{{ trx.customer_name }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ trx.customer_email || '-' }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="font-black text-slate-900 dark:text-white font-mono">
                                        Rp {{ Number(trx.amount).toLocaleString('id-ID') }}
                                    </div>
                                    <div v-if="trx.fee > 0" class="text-[10px] text-slate-400 dark:text-slate-500">Fee: Rp {{ Number(trx.fee).toLocaleString('id-ID') }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                            trx.status === 'PAID' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : '',
                                            trx.status === 'FAILED' ? 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20' : '',
                                            trx.status === 'EXPIRED' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20' : '',
                                            trx.status === 'PENDING' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20' : ''
                                        ]"
                                    >
                                        {{ trx.status === 'PAID' ? 'Berhasil' : (trx.status === 'FAILED' ? 'Gagal' : (trx.status === 'EXPIRED' ? 'Kedaluwarsa' : trx.status)) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ trx.doku_reference }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <Link
                                        :href="route('customer.transactions.show', trx.id)"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400 dark:text-slate-500">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada catatan transaksi pada periode {{ period.label }}.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <div>
                        Menampilkan {{ transactions.from || 0 }} - {{ transactions.to || 0 }} dari total {{ transactions.total }} transaksi
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in transactions.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                link.active ? 'bg-emerald-600 text-white font-bold' : (link.url ? 'bg-slate-100 dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800' : 'text-slate-400 dark:text-slate-600 cursor-not-allowed')
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    period: Object,
    quota: Object,
    summary: Object,
    transactions: Object,
    filters: Object,
});

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const selectedMonth = ref(props.filters.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters.year || new Date().getFullYear());
const currentStatus = ref(props.filters.status || 'all');
const searchQuery = ref(props.filters.search || '');

const applyFilter = () => {
    router.get(route('customer.reports.monthly'), {
        month: selectedMonth.value,
        year: selectedYear.value,
        status: currentStatus.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filterStatus = (st) => {
    currentStatus.value = st;
    applyFilter();
};

const exportUrl = computed(() => {
    return route('customer.reports.monthly.export', {
        month: selectedMonth.value,
        year: selectedYear.value,
        status: currentStatus.value,
        search: searchQuery.value,
    });
});

const printReport = () => {
    window.print();
};
</script>
