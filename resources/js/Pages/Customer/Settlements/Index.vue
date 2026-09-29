<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    balance: Number,
    total_income: Number,
    total_withdrawn: Number,
    pending_withdrawn: Number,
    settlements: Object,
});

const isModalOpen = ref(false);

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

const setQuickAmount = (val) => {
    if (val === 'all') {
        form.amount = Math.floor(props.balance);
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
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitWithdrawal = () => {
    if (Number(form.amount) > props.balance) {
        Swal.fire({
            icon: 'warning',
            title: 'Nominal Melebihi Saldo',
            text: `Nominal penarikan (${formatCurrency(form.amount)}) tidak boleh melebihi saldo tersedia (${formatCurrency(props.balance)}).`,
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
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Saldo & Penarikan Dana</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola pendapatan transaksi QRIS Anda dan ajukan pencairan saldo ke rekening bank atau e-wallet.
                    </p>
                </div>
                <button
                    @click="openModal"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs tracking-tight shadow-sm active:scale-95 transition-all shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Ajukan Tarik Saldo</span>
                </button>
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
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Dana siap dicairkan ke rekening</p>
                    </div>
                </div>

                <!-- Total Income -->
                <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">Total Pendapatan</span>
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
                        <p class="text-xs text-slate-400 mt-0.5">Pantau status verifikasi dan bukti penyelesaian transfer dana Anda.</p>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 font-mono uppercase text-[11px]">
                                <th class="py-3.5 px-6">No. Referensi</th>
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
                                <td colspan="6" class="text-center py-12 text-slate-400 font-sans italic">
                                    Belum ada riwayat penarikan saldo. Klik tombol "Ajukan Tarik Saldo" di atas untuk memulai pencairan.
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
                        <p class="text-xs text-slate-400 mt-0.5">Saldo Tersedia: <strong class="text-emerald-500">{{ formatCurrency(balance) }}</strong></p>
                    </div>
                    <button @click="closeModal" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitWithdrawal" class="space-y-4">
                    <!-- Amount Input -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nominal Penarikan (IDR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-slate-400">Rp</span>
                            <input
                                v-model="form.amount"
                                type="number"
                                min="10000"
                                :max="balance"
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
    </CustomerLayout>
</template>
