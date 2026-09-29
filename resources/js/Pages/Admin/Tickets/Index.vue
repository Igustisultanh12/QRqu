<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tickets: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');
const selectedTicket = ref(null);

const replyForm = useForm({
    status: '',
    admin_reply: '',
});

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
    router.get(route('admin.tickets.index'), {
        status: statusVal,
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
};

const handleSearch = () => {
    router.get(route('admin.tickets.index'), {
        status: currentStatus.value,
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
};

const openReplyModal = (ticket) => {
    selectedTicket.value = ticket;
    replyForm.status = ticket.status;
    replyForm.admin_reply = ticket.admin_reply || '';
};

const closeReplyModal = () => {
    selectedTicket.value = null;
    replyForm.reset();
};

const submitReply = () => {
    replyForm.post(route('admin.tickets.reply', selectedTicket.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeReplyModal();
            Swal.fire({
                icon: 'success',
                title: 'Tiket Berhasil Dibalas',
                text: 'Status dan balasan resmi admin telah diperbarui.',
                confirmButtonColor: '#6366f1',
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Membalas',
                text: Object.values(err)[0] || 'Periksa kembali formulir balasan.',
                confirmButtonColor: '#ef4444',
            });
        }
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Admin - Pusat Resolusi Tiket Pengaduan" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Pusat Resolusi Tiket QRqu HQ</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Pantau kendala transaksi pelanggan, pertanyaan integrasi sistem API/Webhook, dan keluhan teknis merchant.
                </p>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Open -->
                <div
                    @click="applyFilter('OPEN')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'OPEN'
                            ? 'bg-blue-500/15 border-blue-500 ring-2 ring-blue-500/20'
                            : 'bg-white dark:bg-slate-950 border-slate-200/80 dark:border-slate-800 hover:border-blue-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider font-mono">Antrean Baru</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.open }}</div>
                        <p class="text-xs text-slate-400 mt-0.5">Tiket OPEN (Butuh Respons)</p>
                    </div>
                </div>

                <!-- In Progress -->
                <div
                    @click="applyFilter('IN_PROGRESS')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'IN_PROGRESS'
                            ? 'bg-amber-500/15 border-amber-500 ring-2 ring-amber-500/20'
                            : 'bg-white dark:bg-slate-950 border-slate-200/80 dark:border-slate-800 hover:border-amber-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider font-mono">Diteliti</span>
                        <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.in_progress }}</div>
                        <p class="text-xs text-slate-400 mt-0.5">Sedang Dikerjakan IT/CS</p>
                    </div>
                </div>

                <!-- Resolved -->
                <div
                    @click="applyFilter('RESOLVED')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'RESOLVED'
                            ? 'bg-emerald-500/15 border-emerald-500 ring-2 ring-emerald-500/20'
                            : 'bg-white dark:bg-slate-950 border-slate-200/80 dark:border-slate-800 hover:border-emerald-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-mono">Terselesaikan</span>
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.resolved }}</div>
                        <p class="text-xs text-slate-400 mt-0.5">Solusi Diterbitkan</p>
                    </div>
                </div>

                <!-- Closed -->
                <div
                    @click="applyFilter('CLOSED')"
                    :class="[
                        'rounded-3xl p-5 border cursor-pointer transition-all shadow-sm',
                        currentStatus === 'CLOSED'
                            ? 'bg-slate-500/15 border-slate-500 ring-2 ring-slate-500/20'
                            : 'bg-white dark:bg-slate-950 border-slate-200/80 dark:border-slate-800 hover:border-slate-400'
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider font-mono">Ditutup</span>
                        <div class="w-2.5 h-2.5 rounded-full bg-slate-500"></div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.closed }}</div>
                        <p class="text-xs text-slate-400 mt-0.5">Tiket Resmi Ditutup</p>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs & Search -->
            <div class="bg-white dark:bg-slate-950 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
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
                        @click="applyFilter('OPEN')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'OPEN'
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        OPEN
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('IN_PROGRESS')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'IN_PROGRESS'
                                ? 'bg-amber-500 text-slate-950 shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        IN_PROGRESS
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('RESOLVED')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'RESOLVED'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        RESOLVED
                    </button>
                    <button
                        type="button"
                        @click="applyFilter('CLOSED')"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition-all',
                            currentStatus === 'CLOSED'
                                ? 'bg-slate-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        CLOSED
                    </button>
                </div>

                <form @submit.prevent="handleSearch" class="flex items-center gap-2 w-full sm:w-80">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari tiket, subjek, pelanggan..."
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

            <!-- Tickets Table -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 text-slate-400 font-mono uppercase text-[11px]">
                                <th class="py-3.5 px-6">Pelanggan</th>
                                <th class="py-3.5 px-4">No. Tiket</th>
                                <th class="py-3.5 px-4">Kategori & Detail Aduan</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Balasan Admin</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ ticket.customer?.company_name || ticket.user?.name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ ticket.user?.email }}</div>
                                </td>

                                <td class="py-4 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ ticket.ticket_number }}
                                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ formatDate(ticket.created_at) }}</div>
                                </td>

                                <td class="py-4 px-4 max-w-sm">
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ ticket.subject }}</span>
                                    <span class="inline-block text-[10px] font-mono text-indigo-600 dark:text-indigo-400/80 bg-indigo-500/10 px-2 py-0.5 rounded-full mt-1">{{ ticket.category }}</span>
                                    <p class="text-[11px] text-slate-400 mt-1 line-clamp-2 italic">"{{ ticket.description }}"</p>
                                </td>

                                <td class="py-4 px-4">
                                    <span :class="{
                                        'bg-blue-500/10 text-blue-500 ring-blue-500/20': ticket.status === 'OPEN',
                                        'bg-amber-500/10 text-amber-500 ring-amber-500/20': ticket.status === 'IN_PROGRESS',
                                        'bg-emerald-500/10 text-emerald-500 ring-emerald-500/20': ticket.status === 'RESOLVED',
                                        'bg-slate-500/10 text-slate-500 ring-slate-500/20': ticket.status === 'CLOSED',
                                    }" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 ring-inset">
                                        {{ ticket.status }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 max-w-xs text-xs text-slate-500 dark:text-slate-400">
                                    <span v-if="ticket.admin_reply" class="italic line-clamp-2">"{{ ticket.admin_reply }}"</span>
                                    <span v-else class="text-slate-400 italic">Belum dibalas</span>
                                </td>

                                <td class="py-4 px-6 text-right">
                                    <button
                                        @click="openReplyModal(ticket)"
                                        type="button"
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-500/15 dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 hover:text-white dark:hover:text-white rounded-xl text-xs font-bold transition-all shadow-sm"
                                    >
                                        Proses / Balas
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="6" class="text-center py-12 text-slate-400 italic">
                                    Tidak ada tiket laporan yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        Menampilkan {{ tickets.from || 0 }} - {{ tickets.to || 0 }} dari {{ tickets.total }} tiket
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'Link' : 'span'"
                            v-for="(link, i) in tickets.links"
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

        <!-- Modal Balas Tiket Admin (Romei style) -->
        <div v-if="selectedTicket" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeReplyModal"></div>

            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl z-10 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Kelola Tiket: {{ selectedTicket.ticket_number }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Pelanggan: <strong class="text-slate-800 dark:text-slate-200">{{ selectedTicket.customer?.company_name || selectedTicket.user?.name }}</strong>
                        </p>
                    </div>
                    <button @click="closeReplyModal" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Keluhan Pelanggan -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1 text-xs">
                    <span class="font-bold text-slate-500 uppercase text-[10px] font-mono">Keluhan Pelanggan:</span>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ selectedTicket.subject }}</p>
                    <p class="text-slate-600 dark:text-slate-400 italic text-[11px] whitespace-pre-wrap">"{{ selectedTicket.description }}"</p>
                </div>

                <form @submit.prevent="submitReply" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Update Status Penanganan</label>
                        <select
                            v-model="replyForm.status"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required
                        >
                            <option value="OPEN">OPEN (Menunggu Antrean)</option>
                            <option value="IN_PROGRESS">IN_PROGRESS (Sedang Diteliti IT/CS)</option>
                            <option value="RESOLVED">RESOLVED (Berhasil Diselesaikan / Solusi Diberikan)</option>
                            <option value="CLOSED">CLOSED (Tiket Ditutup)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Pesan Balasan Resmi Admin HQ</label>
                        <textarea
                            v-model="replyForm.admin_reply"
                            rows="4"
                            placeholder="Tulis tanggapan solusi atau panduan penanganan kepada pelanggan..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeReplyModal"
                            class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="replyForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <svg v-if="replyForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>{{ replyForm.processing ? 'Menyimpan...' : 'Kirim Balasan & Update' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
