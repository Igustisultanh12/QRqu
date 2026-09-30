<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, router, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
    selected_store_id: {
        type: [String, Number],
        default: 'all',
    },
    selected_store: {
        type: Object,
        default: null,
    },
    doku_fee_enabled: {
        type: Boolean,
        default: true,
    },
    doku_fee_percent: {
        type: Number,
        default: 0.7,
    },
    doku_fee_amount: {
        type: Number,
        default: 0,
    },
    balance: Number,
    total_income: Number,
    total_net: Number,
    total_withdrawn: Number,
    pending_withdrawn: Number,
    settlements: Object,
});

const isModalOpen = ref(false);
const isAddStoreOpen = ref(false);
const selectedStoreId = ref(props.selected_store_id || 'all');

const bankOptions = [
    'BCA',
    'Bank Mandiri',
    'BRI',
    'BNI',
    'BSI (Bank Syariah Indonesia)',
    'CIMB Niaga',
    'Permata Bank',
    'Bank Danamon',
    'SeaBank',
    'Bank Jago',
    'DANA',
    'OVO',
    'GoPay',
    'ShopeePay',
    'Bank Lainnya',
];

const form = useForm({
    amount: '',
    bank_name: '',
    account_number: '',
    account_name: '',
    store_id: props.selected_store_id !== 'all' ? props.selected_store_id : '',
});

const storeForm = useForm({
    name: '',
    code: '',
    description: '',
    address: '',
    phone: '',
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

const pdfExportUrl = computed(() => {
    return route('customer.settlements.pdf', {
        store_id: selectedStoreId.value,
    });
});

const selectStore = (storeId) => {
    selectedStoreId.value = storeId;
    router.get(route('customer.settlements.index'), {
        store_id: storeId,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const activeMaxBalance = computed(() => {
    if (form.store_id && form.store_id !== 'all') {
        const target = props.stores.find(s => String(s.id) === String(form.store_id));
        return target ? target.balance : props.balance;
    }
    return props.balance;
});

const setQuickAmount = (val) => {
    if (val === 'all') {
        form.amount = Math.floor(activeMaxBalance.value);
    } else {
        form.amount = val;
    }
};

const openModal = () => {
    if (props.balance < 10000) {
        Swal.fire({
            icon: 'warning',
            title: 'Saldo Belum Mencukupi',
            text: 'Saldo tersedia Anda minimal Rp 10.000 untuk dapat mengajukan penarikan dana.',
            confirmButtonColor: '#10b981',
        });
        return;
    }
    form.reset();
    form.store_id = selectedStoreId.value !== 'all' ? selectedStoreId.value : '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const openAddStoreModal = () => {
    storeForm.reset();
    isAddStoreOpen.value = true;
};

const closeAddStoreModal = () => {
    isAddStoreOpen.value = false;
    storeForm.reset();
};

const submitAddStore = () => {
    storeForm.post(route('customer.stores.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAddStoreModal();
            Swal.fire({
                icon: 'success',
                title: 'Toko Berhasil Ditambahkan',
                text: 'Toko baru Anda telah aktif dan tercatat pada daftar toko.',
                confirmButtonColor: '#10b981',
            });
        },
    });
};

const submitWithdrawal = () => {
    if (Number(form.amount) > activeMaxBalance.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Nominal Melebihi Saldo',
            text: `Nominal penarikan (${formatCurrency(form.amount)}) tidak boleh melebihi saldo tersedia (${formatCurrency(activeMaxBalance.value)}).`,
            confirmButtonColor: '#10b981',
        });
        return;
    }

    form.post(route('customer.settlements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Pengajuan Berhasil',
                text: 'Permohonan penarikan saldo Anda telah diterima dan masuk tahap Verifikasi oleh Admin.',
                confirmButtonColor: '#10b981',
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mengajukan',
                text: firstError || 'Silakan periksa kembali data formulir penarikan.',
                confirmButtonColor: '#ef4444',
            });
        }
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Saldo & Penarikan Dana - QRqu" />

        <div class="space-y-6">
            <!-- Header Section & Tombol Aksi -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>Saldo & Penarikan Dana</span>
                        <span v-if="selected_store" class="text-xs px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 font-bold">
                            {{ selected_store.name }}
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola saldo transaksi per toko, pantau tarif settlement DOKU, dan cairkan dana langsung ke rekening bank atau e-wallet.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <!-- Tombol Cetak Laporan Saldo PDF -->
                    <a
                        :href="pdfExportUrl"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs tracking-tight shadow-sm shadow-orange-600/20 active:scale-95 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Cetak Laporan Saldo (PDF)</span>
                    </a>

                    <!-- Tombol Ajukan Tarik Saldo -->
                    <button
                        @click="openModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs tracking-tight shadow-sm active:scale-95 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Ajukan Tarik Saldo</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Pilihan Toko & Rincian Saldo -->
            <div class="bg-white dark:bg-slate-900/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/40 dark:bg-slate-900/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold border border-emerald-200 dark:border-emerald-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Tabel Pilihan Toko & Rincian Saldo</h3>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500">Pilih baris toko pada tabel di bawah untuk melihat rincian saldo spesifik, omzet bruto, potongan DOKU 0.7%, dan riwayat pencairan.</p>
                        </div>
                    </div>

                    <button
                        @click="openAddStoreModal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition self-start sm:self-auto"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Tambah Toko</span>
                    </button>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-mono uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-5">Nama Toko</th>
                                <th class="py-3 px-4">Tipe / Kode</th>
                                <th class="py-3 px-4 text-right">Omzet Bruto</th>
                                <th class="py-3 px-4 text-right">Fee DOKU (0.7%)</th>
                                <th class="py-3 px-4 text-right">Omzet Bersih</th>
                                <th class="py-3 px-4 text-right">Saldo Tersedia</th>
                                <th class="py-3 px-4 text-right">Dicairkan</th>
                                <th class="py-3 px-5 text-center">Status / Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <!-- Baris Semua Toko -->
                            <tr
                                @click="selectStore('all')"
                                class="cursor-pointer transition-colors"
                                :class="[
                                    selectedStoreId === 'all'
                                        ? 'bg-emerald-50/70 dark:bg-emerald-950/20 font-semibold'
                                        : 'hover:bg-slate-50/60 dark:hover:bg-slate-900/30'
                                ]"
                            >
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full" :class="selectedStoreId === 'all' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-600'"></span>
                                        <span class="font-bold text-slate-900 dark:text-white">Semua Toko (Akumulasi Global)</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-bold">
                                        {{ stores.length }} Toko
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ formatCurrency(stores.reduce((sum, s) => sum + s.gross_income, 0)) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-rose-500 dark:text-rose-400">
                                    {{ formatCurrency(stores.reduce((sum, s) => sum + s.doku_fee, 0)) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-900 dark:text-white font-bold">
                                    {{ formatCurrency(stores.reduce((sum, s) => sum + s.net_income, 0)) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                    {{ formatCurrency(selectedStoreId === 'all' ? balance : stores.reduce((sum, s) => sum + s.balance, 0)) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(stores.reduce((sum, s) => sum + s.withdrawn, 0)) }}
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <button
                                        type="button"
                                        :class="[
                                            'px-3 py-1 rounded-xl text-[11px] font-bold transition',
                                            selectedStoreId === 'all'
                                                ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20'
                                                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700'
                                        ]"
                                    >
                                        {{ selectedStoreId === 'all' ? '● Aktif' : 'Pilih' }}
                                    </button>
                                </td>
                            </tr>

                            <!-- Baris Masing-Masing Toko -->
                            <tr
                                v-for="st in stores"
                                :key="st.id"
                                @click="selectStore(st.id)"
                                class="cursor-pointer transition-colors"
                                :class="[
                                    String(selectedStoreId) === String(st.id)
                                        ? 'bg-emerald-50/70 dark:bg-emerald-950/20 font-semibold'
                                        : 'hover:bg-slate-50/60 dark:hover:bg-slate-900/30'
                                ]"
                            >
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full" :class="String(selectedStoreId) === String(st.id) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-600'"></span>
                                        <span class="font-bold text-slate-900 dark:text-white">{{ st.name }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    <span
                                        v-if="st.is_default"
                                        class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                                    >
                                        Toko Utama
                                    </span>
                                    <span
                                        v-else-if="st.code"
                                        class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 font-mono"
                                    >
                                        {{ st.code }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ formatCurrency(st.gross_income) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-rose-500 dark:text-rose-400">
                                    {{ formatCurrency(st.doku_fee) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-900 dark:text-white font-bold">
                                    {{ formatCurrency(st.net_income) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                    {{ formatCurrency(st.balance) }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(st.withdrawn) }}
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <button
                                        type="button"
                                        :class="[
                                            'px-3 py-1 rounded-xl text-[11px] font-bold transition',
                                            String(selectedStoreId) === String(st.id)
                                                ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20'
                                                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700'
                                        ]"
                                    >
                                        {{ String(selectedStoreId) === String(st.id) ? '● Aktif' : 'Pilih' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Banner Tarif Settlement DOKU Gateway (0.7%) -->
            <div
                class="p-4 rounded-2xl border transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                :class="doku_fee_enabled
                    ? 'bg-emerald-50/70 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20'
                    : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800'"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0"
                        :class="doku_fee_enabled ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-500'"
                    >
                        %
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">
                                Tarif Settlement DOKU Gateway: {{ doku_fee_enabled ? `${doku_fee_percent}% (Aktif)` : 'Nonaktif (0%)' }}
                            </span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                    doku_fee_enabled
                                        ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                                        : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                {{ doku_fee_enabled ? 'Dipotong Sistem' : 'Bebas Biaya' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            <span v-if="doku_fee_enabled">
                                Total potongan fee settlement: <strong class="text-slate-900 dark:text-white font-mono">{{ formatCurrency(doku_fee_amount) }}</strong> dari bruto {{ formatCurrency(total_income) }}. Omzet bersih: <strong class="text-emerald-600 dark:text-emerald-400 font-mono">{{ formatCurrency(total_net) }}</strong>.
                            </span>
                            <span v-else>
                                Tarif settlement dinonaktifkan oleh Admin. Omzet bruto langsung diteruskan tanpa potongan 0.7%.
                            </span>
                        </p>
                    </div>
                </div>

                <div v-if="selected_store" class="text-xs font-mono font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 self-start sm:self-auto shrink-0">
                    Toko Terpilih: {{ selected_store.name }}
                </div>
            </div>

            <!-- Financial Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Available Balance (Highlighted) -->
                <div class="relative overflow-hidden rounded-3xl p-5 bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent border border-emerald-500/30 dark:border-emerald-500/20 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider font-mono">Saldo Tersedia</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ formatCurrency(balance) }}
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            <span v-if="selected_store">Untuk {{ selected_store.name }}</span>
                            <span v-else>Dana siap dicairkan ke rekening</span>
                        </p>
                    </div>
                </div>

                <!-- Total Income (Gross) -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">Total Pendapatan (Bruto)</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ formatCurrency(total_income) }}
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Akumulasi pembayaran QRIS PAID</p>
                    </div>
                </div>

                <!-- Pending Withdrawal -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider font-mono">Diproses / Verifikasi</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 tracking-tight">
                            {{ formatCurrency(pending_withdrawn) }}
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Dalam antrean verifikasi & transfer</p>
                    </div>
                </div>

                <!-- Total Withdrawn -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">Berhasil Dicairkan</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ formatCurrency(total_withdrawn) }}
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Total dana yang telah selesai ditransfer</p>
                    </div>
                </div>
            </div>

            <!-- Settlement History Table -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Riwayat Pengajuan Penarikan Dana</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Pantau status verifikasi dan bukti transfer dana<span v-if="selected_store"> untuk toko {{ selected_store.name }}</span>.
                        </p>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 font-mono uppercase text-[11px]">
                                <th class="py-3.5 px-6">No. Referensi</th>
                                <th class="py-3.5 px-4">Toko</th>
                                <th class="py-3.5 px-4">Tanggal Pengajuan</th>
                                <th class="py-3.5 px-4">Rekening Tujuan</th>
                                <th class="py-3.5 px-4">Nominal</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-6">Catatan Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="item in settlements.data" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ item.settlement_number }}
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                                        {{ item.store ? item.store.name : 'Semua Toko' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ item.bank_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ item.account_number }} - a.n {{ item.account_name }}</div>
                                </td>
                                <td class="py-4 px-4 font-bold text-slate-900 dark:text-white">
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
                                        Proses Transfer
                                    </span>
                                    <span v-else-if="item.status === 'selesai'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Selesai
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        Ditolak
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400 text-xs max-w-xs">
                                    <span v-if="item.admin_notes" class="italic">"{{ item.admin_notes }}"</span>
                                    <span v-else class="text-slate-400 text-[11px]">-</span>
                                </td>
                            </tr>
                            <tr v-if="settlements.data.length === 0">
                                <td colspan="7" class="text-center py-12 text-slate-400 font-sans italic">
                                    Belum ada riwayat penarikan saldo<span v-if="selected_store"> untuk toko {{ selected_store.name }}</span>. Klik tombol "Ajukan Tarik Saldo" di atas untuk memulai pencairan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List -->
                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
                    <div v-for="item in settlements.data" :key="item.id" class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-xs text-emerald-600 dark:text-emerald-400">{{ item.settlement_number }}</span>
                            <span v-if="item.status === 'verifikasi'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                Verifikasi
                            </span>
                            <span v-else-if="item.status === 'proses'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                Proses Transfer
                            </span>
                            <span v-else-if="item.status === 'selesai'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                Selesai
                            </span>
                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-500 border border-rose-500/20">
                                Ditolak
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Toko:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ item.store ? item.store.name : 'Semua Toko' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Nominal:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(item.amount) }}</span>
                        </div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/60 p-2.5 rounded-xl border border-slate-200/60 dark:border-slate-800">
                            <p class="font-bold text-slate-900 dark:text-white">{{ item.bank_name }}</p>
                            <p class="font-mono mt-0.5">{{ item.account_number }} - a.n {{ item.account_name }}</p>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>{{ formatDate(item.created_at) }}</span>
                            <span v-if="item.admin_notes" class="italic truncate max-w-[180px]">"{{ item.admin_notes }}"</span>
                        </div>
                    </div>
                    <div v-if="settlements.data.length === 0" class="text-center py-10 text-slate-400 text-xs italic">
                        Belum ada riwayat penarikan saldo.
                    </div>
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
                                    ? 'bg-emerald-500 text-slate-950 font-bold'
                                    : link.url
                                        ? 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                        : 'text-slate-400 opacity-40 cursor-not-allowed'
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajukan Tarik Saldo -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeModal"></div>

            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl z-10 space-y-5 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 dark:border-slate-800">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Formulir Tarik Saldo</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Saldo Tersedia: <strong class="text-emerald-500">{{ formatCurrency(activeMaxBalance) }}</strong></p>
                    </div>
                    <button @click="closeModal" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitWithdrawal" class="space-y-4">
                    <!-- Store Selector -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Toko Sumber Saldo</label>
                        <select
                            v-model="form.store_id"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">Semua Toko / Saldo Global (Maks: {{ formatCurrency(balance) }})</option>
                            <option v-for="st in stores" :key="st.id" :value="st.id">
                                {{ st.name }} (Saldo: {{ formatCurrency(st.balance) }})
                            </option>
                        </select>
                        <span v-if="form.errors.store_id" class="text-[11px] text-red-500 font-bold block">{{ form.errors.store_id }}</span>
                    </div>

                    <!-- Amount Input -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nominal Penarikan (IDR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-slate-400">Rp</span>
                            <input
                                v-model="form.amount"
                                type="number"
                                min="10000"
                                :max="activeMaxBalance"
                                placeholder="Min. 10.000"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required
                            />
                        </div>
                        <span v-if="form.errors.amount" class="text-[11px] text-red-500 font-bold block">{{ form.errors.amount }}</span>

                        <!-- Quick Amount Buttons -->
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button
                                v-for="amt in [50000, 100000, 250000, 500000]"
                                :key="amt"
                                type="button"
                                @click="setQuickAmount(amt)"
                                class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-emerald-500/10 hover:text-emerald-500 transition-colors"
                            >
                                {{ formatCurrency(amt) }}
                            </button>
                            <button
                                type="button"
                                @click="setQuickAmount('all')"
                                class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-slate-950 transition-colors"
                            >
                                Tarik Semua
                            </button>
                        </div>
                    </div>

                    <!-- Bank Name -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Bank atau E-Wallet Tujuan</label>
                        <select
                            v-model="form.bank_name"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        >
                            <option value="" disabled>-- Pilih Bank / E-Wallet --</option>
                            <option v-for="bank in bankOptions" :key="bank" :value="bank">{{ bank }}</option>
                        </select>
                        <span v-if="form.errors.bank_name" class="text-[11px] text-red-500 font-bold block">{{ form.errors.bank_name }}</span>
                    </div>

                    <!-- Account Number -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nomor Rekening / Nomor HP E-Wallet</label>
                        <input
                            v-model="form.account_number"
                            type="text"
                            placeholder="Contoh: 1234567890 atau 08123456789"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        />
                        <span v-if="form.errors.account_number" class="text-[11px] text-red-500 font-bold block">{{ form.errors.account_number }}</span>
                    </div>

                    <!-- Account Name -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Lengkap Pemilik Rekening</label>
                        <input
                            v-model="form.account_name"
                            type="text"
                            placeholder="Nama sesuai buku tabungan atau akun e-wallet"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        />
                        <span v-if="form.errors.account_name" class="text-[11px] text-red-500 font-bold block">{{ form.errors.account_name }}</span>
                    </div>

                    <!-- Info Box -->
                    <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-2xl flex items-start gap-2.5 text-[11px] text-amber-800 dark:text-amber-300">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>
                            Penarikan akan segera masuk tahap <strong>Verifikasi</strong> oleh tim admin. Pastikan nomor rekening dan nama pemilik sudah tepat agar proses transfer lancar.
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs tracking-tight shadow-sm active:scale-95 disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-slate-950" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>{{ form.processing ? 'Mengirim Pengajuan...' : 'Kirim Pengajuan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Toko Baru -->
        <div v-if="isAddStoreOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeAddStoreModal"></div>

            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl z-10 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Tambah Toko Baru</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Kelola saldo dan transaksi per outlet / cabang toko.</p>
                    </div>
                    <button @click="closeAddStoreModal" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitAddStore" class="space-y-3.5">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Toko / Outlet *</label>
                        <input
                            v-model="storeForm.name"
                            type="text"
                            placeholder="Contoh: Toko Cabang Sudirman / Toko Online"
                            required
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        <span v-if="storeForm.errors.name" class="text-[11px] text-rose-500">{{ storeForm.errors.name }}</span>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Kode Unik Toko (Opsional)</label>
                        <input
                            v-model="storeForm.code"
                            type="text"
                            placeholder="Contoh: SDR-01 / ONLINE"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono uppercase text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Deskripsi / Keterangan</label>
                        <input
                            v-model="storeForm.description"
                            type="text"
                            placeholder="Contoh: Penjualan produk digital via API"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nomor Telepon</label>
                            <input
                                v-model="storeForm.phone"
                                type="text"
                                placeholder="08..."
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Kota / Alamat</label>
                            <input
                                v-model="storeForm.address"
                                type="text"
                                placeholder="Jakarta Selatan"
                                class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeAddStoreModal"
                            class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="storeForm.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition disabled:opacity-50"
                        >
                            {{ storeForm.processing ? 'Menyimpan...' : 'Simpan Toko' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerLayout>
</template>
