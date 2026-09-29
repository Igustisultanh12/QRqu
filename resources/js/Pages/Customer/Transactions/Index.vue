<template>
    <CustomerLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <span>Riwayat Transaksi & Tagihan</span>
                <span v-if="hasPendingTransactions" class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 px-3 py-1 rounded-full border border-amber-200 dark:border-amber-500/30">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Live Monitoring Gateway (Auto-Sync)</span>
                </span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter Bar & Export -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 transition-colors duration-200">
                <form @submit.prevent="applyFilters" class="flex flex-wrap items-center gap-3 flex-1">
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Cari TRX / Invoice / External ID..."
                        class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 w-full sm:w-64"
                    />

                    <select
                        v-model="filterForm.status"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="PAID">PAID (Lunas)</option>
                        <option value="PENDING">PENDING</option>
                        <option value="FAILED">FAILED</option>
                        <option value="EXPIRED">EXPIRED</option>
                        <option value="CANCELLED">CANCELLED</option>
                    </select>

                    <button
                        type="submit"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold transition border border-slate-200 dark:border-slate-700"
                    >
                        Filter
                    </button>
                </form>

                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="syncAll"
                        :disabled="isSyncingAll"
                        class="px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30 font-semibold text-xs transition flex items-center justify-center space-x-1.5"
                        title="Periksa status transaksi PENDING ke server gateway sekarang"
                    >
                        <svg v-if="isSyncingAll" class="animate-spin h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>{{ isSyncingAll ? 'Mengecek...' : '🔄 Sinkron Gateway' }}</span>
                    </button>

                    <a
                        :href="route('customer.transactions.export', filterForm)"
                        class="px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 font-semibold text-xs transition flex items-center justify-center space-x-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Transaction ID</th>
                                <th class="p-4">Invoice / External ID</th>
                                <th class="p-4">Nominal</th>
                                <th class="p-4">Metode</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Ref Transaksi</th>
                                <th class="p-4">Waktu Dibuat</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada data transaksi yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                            <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4 font-mono font-bold text-slate-900 dark:text-slate-200">{{ trx.id }}</td>
                                <td class="p-4">
                                    <div class="font-mono font-semibold text-slate-900 dark:text-white">{{ trx.invoice_id }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ trx.external_id }}</div>
                                </td>
                                <td class="p-4 font-bold text-slate-900 dark:text-white">
                                    Rp {{ Number(trx.amount).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4 uppercase font-semibold text-emerald-600 dark:text-emerald-400">QRIS</td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase',
                                            trx.status === 'PAID' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                            trx.status === 'PENDING' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                                            'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ trx.status }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ trx.doku_reference || '-' }}</td>
                                <td class="p-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ new Date(trx.created_at).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4 text-right">
                                    <Link :href="route('customer.transactions.show', trx.id)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700">
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-1">
                    <Link
                        v-for="(link, idx) in transactions.links"
                        :key="idx"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1 rounded-lg text-xs font-semibold transition',
                            link.active ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:text-white border border-slate-200 dark:border-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none' : ''
                        ]"
                    />
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    transactions: Object,
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const isSyncingAll = ref(false);
let pollTimer = null;

const hasPendingTransactions = computed(() => {
    return props.transactions?.data?.some(t => t.status === 'PENDING') ?? false;
});

const syncAll = () => {
    isSyncingAll.value = true;
    router.reload({
        only: ['transactions'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isSyncingAll.value = false;
        }
    });
};

const startAutoPoll = () => {
    if (pollTimer) clearInterval(pollTimer);
    if (!hasPendingTransactions.value) return;

    // Polling live setiap 4 detik saat ada transaksi PENDING di halaman
    pollTimer = setInterval(() => {
        if (!hasPendingTransactions.value) {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            return;
        }
        router.reload({
            only: ['transactions'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 4000);
};

watch(hasPendingTransactions, (newVal) => {
    if (newVal) {
        startAutoPoll();
    } else if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});

onMounted(() => {
    startAutoPoll();
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});

const applyFilters = () => {
    router.get(route('customer.transactions.index'), filterForm, { preserveState: true });
};
</script>
