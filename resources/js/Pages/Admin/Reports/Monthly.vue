<template>
    <AdminLayout>
        <template #header>Laporan Bulanan Platform</template>

        <div class="space-y-6">
            <!-- Header Filter & Quick Action -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>Laporan Bulanan: {{ period.label }}</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-normal">
                            {{ quota.merchant_name }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Pemantauan transaksi berhasil, gagal, nama transaksi, dan kuota terpakai merchant.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Selector Merchant -->
                    <select
                        v-model="selectedMerchant"
                        @change="applyFilter"
                        class="bg-slate-950 border border-slate-800 text-xs text-slate-200 rounded-xl px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 max-w-[180px]"
                    >
                        <option value="all">Semua Merchant</option>
                        <option v-for="m in merchants" :key="m.id" :value="m.id">
                            {{ m.name }} ({{ m.company_name || 'Individual' }})
                        </option>
                    </select>

                    <!-- Selector Bulan -->
                    <select
                        v-model="selectedMonth"
                        @change="applyFilter"
                        class="bg-slate-950 border border-slate-800 text-xs text-slate-200 rounded-xl px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option v-for="(name, mIndex) in monthNames" :key="mIndex + 1" :value="mIndex + 1">
                            {{ name }}
                        </option>
                    </select>

                    <!-- Selector Tahun -->
                    <select
                        v-model="selectedYear"
                        @change="applyFilter"
                        class="bg-slate-950 border border-slate-800 text-xs text-slate-200 rounded-xl px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">
                            {{ y }}
                        </option>
                    </select>

                    <!-- Shortcut ke Pengaturan Harga & Kuota -->
                    <Link
                        :href="route('admin.settings.index')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Set Harga & Kuota
                    </Link>

                    <!-- Ekspor CSV -->
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
                    >
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Ekspor CSV
                    </a>

                    <!-- Cetak Laporan -->
                    <button
                        @click="printReport"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
                    >
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak
                    </button>
                </div>
            </div>

            <!-- Card Kuota Bulanan & Informasi Pengaturan -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-950 p-6 rounded-3xl border border-slate-800 shadow-lg">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pemantauan Kuota Bulanan (Admin Control)</span>
                        </div>
                        <h3 class="text-xl font-black text-white mt-1">
                            {{ Number(quota.used).toLocaleString('id-ID') }}
                            <span class="text-sm font-normal text-slate-400">/ {{ Number(quota.limit).toLocaleString('id-ID') }} transaksi</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Status kuota: <span class="font-bold text-indigo-400">{{ Number(quota.remaining).toLocaleString('id-ID') }} kuota tersisa</span>
                            <span v-if="quota.is_single_merchant"> untuk merchant ini.</span>
                            <span v-else> di seluruh platform.</span>
                        </p>
                    </div>

                    <div class="text-left md:text-right">
                        <span class="text-xs text-slate-400">Standar Harga Langganan Bulanan</span>
                        <div class="text-base font-black text-white">Rp {{ Number(quota.monthly_price).toLocaleString('id-ID') }} <span class="text-xs font-normal text-slate-400">/ bln</span></div>
                        <Link :href="route('admin.settings.index')" class="text-[11px] text-indigo-400 hover:underline">
                            Konfigurasi di Pengaturan &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Progress Bar Kuota -->
                <div class="mt-4">
                    <div class="w-full bg-slate-800/80 rounded-full h-3 overflow-hidden p-0.5 border border-slate-700/50">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="[
                                quota.percentage > 90 ? 'bg-rose-500' : (quota.percentage > 70 ? 'bg-amber-500' : 'bg-indigo-500')
                            ]"
                            :style="{ width: quota.percentage + '%' }"
                        ></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400 mt-1.5 font-mono">
                        <span>0%</span>
                        <span :class="quota.percentage > 90 ? 'text-rose-400 font-bold' : 'text-slate-300'">{{ quota.percentage }}% Terpakai</span>
                        <span>100% (Kapasitas: {{ Number(quota.limit).toLocaleString('id-ID') }})</span>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Statistik 4 Kolom: Berhasil vs Gagal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Transaksi Berhasil -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Transaksi Berhasil</span>
                        <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-emerald-400 mt-2">
                        {{ Number(summary.successful_count).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-bold text-slate-200 mt-1">
                        Rp {{ Number(summary.successful_amount).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Status: PAID / DOKU SUCCESS</div>
                </div>

                <!-- Transaksi Gagal -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Transaksi Gagal</span>
                        <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-rose-400 mt-2">
                        {{ Number(summary.failed_count).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-bold text-slate-200 mt-1">
                        Rp {{ Number(summary.failed_amount).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Status: FAILED / CANCEL</div>
                </div>

                <!-- Transaksi Kedaluwarsa -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Kedaluwarsa (Expired)</span>
                        <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-amber-400 mt-2">
                        {{ Number(summary.expired_count).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs font-bold text-slate-200 mt-1">
                        Rp {{ Number(summary.expired_amount).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Status: EXPIRED</div>
                </div>

                <!-- Rasio Sukses & Total Transaksi -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Tingkat Keberhasilan</span>
                        <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-indigo-400 mt-2">
                        {{ summary.success_rate }}%
                    </div>
                    <div class="text-xs font-bold text-slate-200 mt-1">
                        Total {{ Number(summary.total_count).toLocaleString('id-ID') }} Transaksi
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Omset: Rp {{ Number(summary.total_amount).toLocaleString('id-ID') }}</div>
                </div>
            </div>

            <!-- Tabel Transaksi Lengkap -->
            <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden">
                <!-- Filter Bar -->
                <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
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
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                                currentStatus === st.val
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-slate-900 text-slate-400 hover:text-slate-200 hover:bg-slate-800'
                            ]"
                        >
                            {{ st.label }}
                        </button>
                    </div>

                    <div class="relative w-full sm:w-72">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilter"
                            type="text"
                            placeholder="Cari nama transaksi / merchant..."
                            class="w-full pl-9 pr-3.5 py-1.5 text-xs bg-slate-900 border border-slate-800 rounded-xl text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        />
                        <svg class="w-4 h-4 absolute left-3 top-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/60 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Tanggal & Jam</th>
                                <th class="py-3 px-4">Nama Transaksi</th>
                                <th class="py-3 px-4">Merchant</th>
                                <th class="py-3 px-4">No. Invoice & ID</th>
                                <th class="py-3 px-4 text-right">Nominal</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Ref DOKU</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-900/40 transition">
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ trx.created_at }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-white max-w-xs truncate" :title="trx.nama_transaksi">
                                        {{ trx.nama_transaksi }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">Ext: {{ trx.external_id }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-indigo-400 font-semibold">{{ trx.merchant_name }}</div>
                                    <div class="text-[10px] text-slate-500">Pmb: {{ trx.customer_name }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    <div class="text-slate-300">{{ trx.invoice_id }}</div>
                                    <div class="text-slate-500 text-[10px]">{{ trx.id }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="font-black text-white font-mono">
                                        Rp {{ Number(trx.amount).toLocaleString('id-ID') }}
                                    </div>
                                    <div v-if="trx.fee > 0" class="text-[10px] text-slate-500">Fee: Rp {{ Number(trx.fee).toLocaleString('id-ID') }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                            trx.status === 'PAID' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : '',
                                            trx.status === 'FAILED' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : '',
                                            trx.status === 'EXPIRED' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '',
                                            trx.status === 'PENDING' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : ''
                                        ]"
                                    >
                                        {{ trx.status === 'PAID' ? 'Berhasil' : (trx.status === 'FAILED' ? 'Gagal' : (trx.status === 'EXPIRED' ? 'Kedaluwarsa' : trx.status)) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ trx.doku_reference }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <Link
                                        :href="route('admin.transactions.show', trx.id)"
                                        class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-500">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada data transaksi pada periode {{ period.label }}.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <div>
                        Menampilkan {{ transactions.from || 0 }} - {{ transactions.to || 0 }} dari {{ transactions.total }} transaksi
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in transactions.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                link.active ? 'bg-indigo-600 text-white font-bold' : (link.url ? 'bg-slate-900 text-slate-300 hover:bg-slate-800' : 'text-slate-600 cursor-not-allowed')
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    period: Object,
    quota: Object,
    summary: Object,
    merchants: Array,
    transactions: Object,
    filters: Object,
});

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const selectedMonth = ref(props.filters.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters.year || new Date().getFullYear());
const selectedMerchant = ref(props.filters.customer_id || 'all');
const currentStatus = ref(props.filters.status || 'all');
const searchQuery = ref(props.filters.search || '');

const applyFilter = () => {
    router.get(route('admin.reports.monthly'), {
        month: selectedMonth.value,
        year: selectedYear.value,
        customer_id: selectedMerchant.value,
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
    return route('admin.reports.monthly.export', {
        month: selectedMonth.value,
        year: selectedYear.value,
        customer_id: selectedMerchant.value,
        status: currentStatus.value,
        search: searchQuery.value,
    });
});

const printReport = () => {
    window.print();
};
</script>
