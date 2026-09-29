<script setup>
import { ref } from 'vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    settlements: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');
const selectedSettlement = ref(null);

const statusForm = useForm({
    status: '',
    admin_notes: '',
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const applyFilter = (statusVal) => {
    currentStatus.value = statusVal;
    router.get(route('admin.settlements.index'), {
        status: statusVal,
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
};

const handleSearch = () => {
    router.get(route('admin.settlements.index'), {
        status: currentStatus.value,
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
};

const openManageModal = (item) => {
    selectedSettlement.value = item;
    statusForm.status = item.status;
    statusForm.admin_notes = item.admin_notes || '';
};

const closeManageModal = () => {
    selectedSettlement.value = null;
    statusForm.reset();
};

const submitStatusUpdate = () => {
    statusForm.post(route('admin.settlements.status', selectedSettlement.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeManageModal();
            Swal.fire({
                icon: 'success',
                title: 'Status Diperbarui',
                text: 'Status penarikan dana berhasil diperbarui.',
                confirmButtonColor: '#6366f1',
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memperbarui',
                text: Object.values(err)[0] || 'Terjadi kesalahan sistem.',
                confirmButtonColor: '#ef4444',
            });
        }
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Admin - Manajemen Penarikan Saldo" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Manajemen Penarikan Saldo Pelanggan</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Verifikasi, proses transfer bank, dan selesaikan pengajuan pencairan saldo dari seluruh merchant QRqu.
                    </p>
                </div>
            </div>

            <!-- Stats Overview Cards (3 Status Utama) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Verifikasi Card -->
                <div
                    @click="applyFilter('verifikasi')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'verifikasi'
                            ? 'bg-amber-500/15 border-amber-500 ring-2 ring-amber-500/20'
                            : 'bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border-slate-200/80 dark:border-slate-800 hover:border-amber-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider font-mono">1. Verifikasi</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">
                            {{ stats.verifikasi_count }} <span class="text-xs font-normal text-slate-400">pengajuan</span>
                        </div>
                        <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 mt-1">
                            {{ formatCurrency(stats.verifikasi_amount) }}
                        </p>
                    </div>
                </div>

                <!-- Proses Card -->
                <div
                    @click="applyFilter('proses')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'proses'
                            ? 'bg-blue-500/15 border-blue-500 ring-2 ring-blue-500/20'
                            : 'bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border-slate-200/80 dark:border-slate-800 hover:border-blue-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider font-mono">2. Proses Transfer</span>
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">
                            {{ stats.proses_count }} <span class="text-xs font-normal text-slate-400">pengajuan</span>
                        </div>
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mt-1">
                            {{ formatCurrency(stats.proses_amount) }}
                        </p>
                    </div>
                </div>

                <!-- Selesai Card -->
                <div
                    @click="applyFilter('selesai')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'selesai'
                            ? 'bg-emerald-500/15 border-emerald-500 ring-2 ring-emerald-500/20'
                            : 'bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border-slate-200/80 dark:border-slate-800 hover:border-emerald-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-mono">3. Selesai</span>
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">
                            {{ stats.selesai_count }} <span class="text-xs font-normal text-slate-400">pengajuan</span>
                        </div>
                        <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ formatCurrency(stats.selesai_amount) }}
                        </p>
                    </div>
                </div>

                <!-- Total All Card -->
                <div
                    @click="applyFilter('all')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'all'
                            ? 'bg-indigo-500/15 border-indigo-500 ring-2 ring-indigo-500/20'
                            : 'bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border-slate-200/80 dark:border-slate-800 hover:border-indigo-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">Semua Pengajuan</span>
                        <div class="w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">
                            {{ stats.total_count }} <span class="text-xs font-normal text-slate-400">pengajuan</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            {{ stats.ditolak_count }} ditolak
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Controls -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="applyFilter('all')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'all'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('verifikasi')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'verifikasi'
                                ? 'bg-amber-500 text-slate-950 shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        Verifikasi
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('proses')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'proses'
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        Proses Transfer
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('selesai')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'selesai'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        Selesai
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('ditolak')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'ditolak'
                                ? 'bg-rose-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        Ditolak
                    </button>
                </div>

                <!-- Search Input -->
                <form @submit.prevent="handleSearch" class="flex items-center gap-2 w-full sm:w-80">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari merchant, nomor, bank..."
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                    <button
                        type="submit"
                        class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/15 dark:hover:bg-indigo-500/25 text-indigo-700 dark:text-indigo-400 rounded-xl text-xs font-bold shrink-0 transition-colors"
                    >
                        Cari
                    </button>
                </form>
            </div>

            <!-- Table of Settlements -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 font-mono uppercase text-[11px]">
                                <th class="py-3.5 px-6">No. Referensi</th>
                                <th class="py-3.5 px-4">Merchant / Pelanggan</th>
                                <th class="py-3.5 px-4">Rekening Tujuan & Bank</th>
                                <th class="py-3.5 px-4">Nominal</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Catatan</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="item in settlements.data" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ item.settlement_number }}
                                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ formatDate(item.created_at) }}</div>
                                </td>
                                
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ item.customer?.company_name || item.customer?.name || item.user?.name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ item.user?.email }}</div>
                                </td>

                                <td class="py-4 px-4">
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded font-bold text-[11px] text-slate-800 dark:text-slate-200">
                                        {{ item.bank_name }}
                                    </span>
                                    <div class="font-mono text-xs font-semibold text-slate-900 dark:text-white mt-1">{{ item.account_number }}</div>
                                    <div class="text-[11px] text-slate-500">a.n {{ item.account_name }}</div>
                                </td>

                                <td class="py-4 px-4 font-black text-slate-900 dark:text-white">
                                    {{ formatCurrency(item.amount) }}
                                </td>

                                <td class="py-4 px-4">
                                    <!-- 3 Status Utama: verifikasi, proses, selesai (+ ditolak) -->
                                    <span v-if="item.status === 'verifikasi'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        Verifikasi
                                    </span>
                                    <span v-else-if="item.status === 'proses'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Proses
                                    </span>
                                    <span v-else-if="item.status === 'selesai'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Selesai
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        Ditolak
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-slate-500 dark:text-slate-400 max-w-xs text-xs truncate">
                                    <span v-if="item.admin_notes" class="italic" :title="item.admin_notes">"{{ item.admin_notes }}"</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <td class="py-4 px-6 text-right">
                                    <button
                                        @click="openManageModal(item)"
                                        type="button"
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-500/15 dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 hover:text-white dark:hover:text-white rounded-xl text-xs font-bold transition-all shadow-sm"
                                    >
                                        Kelola Status
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="settlements.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400 italic">
                                    Tidak ada data pengajuan penarikan saldo yang sesuai filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="settlements.links && settlements.links.length > 3" class="p-4 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Menampilkan {{ settlements.from || 0 }} - {{ settlements.to || 0 }} dari {{ settlements.total }} pengajuan
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'Link' : 'span'"
                            v-for="(link, i) in settlements.links"
                            :key="i"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1 text-xs rounded-lg transition-colors',
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : link.url
                                        ? 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                        : 'text-slate-400 opacity-40 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Kelola Status Penarikan -->
        <div v-if="selectedSettlement" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeManageModal"></div>

            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl z-10 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Kelola Penarikan: {{ selectedSettlement.settlement_number }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Pelanggan: <strong class="text-slate-800 dark:text-slate-200">{{ selectedSettlement.customer?.company_name || selectedSettlement.customer?.name }}</strong>
                        </p>
                    </div>
                    <button @click="closeManageModal" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Info Box Transfer Tujuan -->
                <div class="bg-slate-50 dark:bg-slate-950 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nominal Tarik:</span>
                        <span class="font-black text-sm text-emerald-600 dark:text-emerald-400">{{ formatCurrency(selectedSettlement.amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Bank / E-Wallet:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedSettlement.bank_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nomor Rekening:</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ selectedSettlement.account_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Nama Penerima:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedSettlement.account_name }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitStatusUpdate" class="space-y-4">
                    <!-- Status Selector: 3 status verifikasi, proses, selesai (+ ditolak) -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Status Penarikan</label>
                        <select
                            v-model="statusForm.status"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required
                        >
                            <option value="verifikasi">1. Verifikasi (Menunggu Cek / Validasi Data)</option>
                            <option value="proses">2. Proses (Admin Sedang Melakukan Transfer Dana)</option>
                            <option value="selesai">3. Selesai (Dana Berhasil Masuk ke Rekening Pelanggan)</option>
                            <option value="ditolak">Ditolak (Data Rekening Salah / Permohonan Dibatalkan)</option>
                        </select>
                    </div>

                    <!-- Admin Notes -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Catatan Admin / No. Referensi Transfer</label>
                        <textarea
                            v-model="statusForm.admin_notes"
                            rows="3"
                            placeholder="Contoh: Transfer via BCA Ref No. 9812481 pada jam 14:30 WIB..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeManageModal"
                            class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="statusForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <svg v-if="statusForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>{{ statusForm.processing ? 'Menyimpan...' : 'Perbarui Status' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
