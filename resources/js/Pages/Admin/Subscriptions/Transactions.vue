<template>
    <AdminLayout title="Transaksi Langganan (Subs)">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Revenue Gateway</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                        Transaksi Subs
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Log transaksi pembayaran paket langganan merchant via gateway QRIS DOKU.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.plans.index')"
                        class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition border border-slate-300 dark:border-slate-700 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <span>Kelola Paket & Harga</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Highlight Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Omset Subs</span>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                        {{ stats.total_revenue_formatted }}
                    </div>
                    <span class="text-[10px] text-emerald-500 font-semibold flex items-center gap-1 mt-1">
                        ● Akumulatif keseluruhan
                    </span>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Omset Bulan Ini</span>
                    <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                        {{ stats.this_month_revenue_formatted }}
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium mt-1 block">Periode aktif saat ini</span>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Subs Lunas (PAID)</span>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                        {{ Number(stats.paid_count).toLocaleString('id-ID') }}
                    </div>
                    <span class="text-[10px] text-emerald-500 font-semibold mt-1 block">Berhasil teraktivasi</span>
                </div>

                <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Menunggu Bayar</span>
                    <div class="text-xl sm:text-2xl font-black text-amber-500 mt-1 font-mono">
                        {{ Number(stats.pending_count).toLocaleString('id-ID') }}
                    </div>
                    <span class="text-[10px] text-amber-500/80 font-medium mt-1 block">Pending QRIS checkout</span>
                </div>
            </div>

            <!-- Filter & Search Section -->
            <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pencarian</label>
                        <input
                            v-model="filterForm.search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari ID, SUB-*, Merchant, DOKU Ref..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-orange-500"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status Bayar</label>
                        <select
                            v-model="filterForm.status"
                            @change="applyFilters"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-orange-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="PAID">PAID (Lunas)</option>
                            <option value="PENDING">PENDING (Menunggu)</option>
                            <option value="EXPIRED">EXPIRED (Kedaluwarsa)</option>
                            <option value="CANCELLED">CANCELLED (Batal)</option>
                        </select>
                    </div>

                    <!-- Customer / Merchant Filter -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Merchant</label>
                        <select
                            v-model="filterForm.customer_id"
                            @change="applyFilters"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-orange-500"
                        >
                            <option value="">Semua Merchant</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.name }} {{ c.company_name ? '(' + c.company_name + ')' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-end gap-2">
                        <button
                            @click="applyFilters"
                            type="button"
                            class="flex-1 py-2 px-3 bg-slate-950 text-white dark:bg-white dark:text-slate-950 font-bold text-xs rounded-xl hover:bg-slate-800 dark:hover:bg-slate-100 transition shadow-sm"
                        >
                            Filter Data
                        </button>
                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            type="button"
                            class="py-2 px-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 font-bold text-xs rounded-xl transition"
                            title="Reset Filter"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px] font-bold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="py-3.5 px-4">Waktu</th>
                                <th class="py-3.5 px-4">ID Transaksi & Ref</th>
                                <th class="py-3.5 px-4">Merchant</th>
                                <th class="py-3.5 px-4">Paket Subs</th>
                                <th class="py-3.5 px-4 text-right">Nominal</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4">DOKU Ref</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-sans">
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <p class="font-semibold">Belum ada transaksi langganan</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Transaksi pembayaran paket langganan merchant akan muncul di sini.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="trx in transactions.data"
                                :key="trx.id"
                                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                    {{ trx.created_at }}
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-mono font-bold text-slate-900 dark:text-white text-xs">
                                        {{ trx.external_id }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono">
                                        Inv: {{ trx.invoice_id }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ trx.customer?.name || 'Merchant' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ trx.customer?.email }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 dark:bg-orange-950/40 text-orange-700 dark:text-orange-400 border border-orange-200 dark:border-orange-800/40">
                                        {{ trx.plan_name }}
                                    </span>
                                    <span v-if="trx.plan_duration_days" class="text-[10px] text-slate-400 block mt-0.5 font-mono">
                                        {{ trx.plan_duration_days }} Hari
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ trx.amount_formatted }}
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wider uppercase font-mono',
                                            trx.status === 'PAID'
                                                ? 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800'
                                                : (trx.status === 'PENDING' || trx.status === 'CREATED'
                                                    ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800'
                                                    : 'bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800')
                                        ]"
                                    >
                                        {{ trx.status === 'PAID' ? 'LUNAS' : trx.status }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ trx.doku_reference || '-' }}
                                </td>

                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a
                                            :href="route('checkout.show', trx.invoice_id)"
                                            target="_blank"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Buka Halaman Checkout QRIS"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>

                                        <button
                                            @click="openDetail(trx)"
                                            type="button"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Lihat Detail Transaksi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Menampilkan {{ transactions.from || 0 }} - {{ transactions.to || 0 }} dari {{ transactions.total }} transaksi subs
                    </span>
                    <div class="flex items-center space-x-1">
                        <template v-for="(link, lIdx) in transactions.links" :key="lIdx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 text-xs rounded-xl font-bold transition',
                                    link.active
                                        ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                                ]"
                                v-html="link.label"
                            />
                            <span v-else class="px-2 py-1 text-xs text-slate-300 dark:text-slate-700" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Detail Modal -->
            <div v-if="activeTrx" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="activeTrx = null"></div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl relative z-10 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">Detail Transaksi Subs</h3>
                        </div>
                        <button @click="activeTrx = null" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">External ID</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ activeTrx.external_id }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Invoice ID</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">{{ activeTrx.invoice_id }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Merchant</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ activeTrx.customer?.name }} ({{ activeTrx.customer?.company_name }})</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Paket Langganan</span>
                            <span class="font-bold text-orange-600 dark:text-orange-400">{{ activeTrx.plan_name }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Nominal Tagihan</span>
                            <span class="font-mono font-bold text-base text-slate-900 dark:text-white">{{ activeTrx.amount_formatted }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Status Pembayaran</span>
                            <span class="font-mono font-bold" :class="activeTrx.status === 'PAID' ? 'text-emerald-500' : 'text-amber-500'">{{ activeTrx.status }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">DOKU Gateway Ref</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">{{ activeTrx.doku_reference || 'Belum ada ref (Pending)' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Waktu Transaksi</span>
                            <span class="text-slate-700 dark:text-slate-300 font-mono">{{ activeTrx.created_at }}</span>
                        </div>
                        <div v-if="activeTrx.paid_at" class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800/60">
                            <span class="text-slate-400">Waktu Lunas</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{{ activeTrx.paid_at }}</span>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <a
                            :href="route('checkout.show', activeTrx.invoice_id)"
                            target="_blank"
                            class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-md shadow-orange-600/20"
                        >
                            <span>Buka Portal Bayar QRIS</span>
                            <span>↗</span>
                        </a>
                        <button
                            @click="activeTrx = null"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-200 transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    transactions: Object,
    customers: Array,
    plans: Array,
    filters: Object,
    stats: Object,
});

const filterForm = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    customer_id: props.filters?.customer_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const activeTrx = ref(null);

const hasActiveFilters = computed(() => {
    return Boolean(
        filterForm.value.search ||
        filterForm.value.status ||
        filterForm.value.customer_id ||
        filterForm.value.date_from ||
        filterForm.value.date_to
    );
});

const applyFilters = () => {
    router.get(route('admin.subscriptions.transactions'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.value = {
        search: '',
        status: '',
        customer_id: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

const openDetail = (trx) => {
    activeTrx.value = trx;
};
</script>
