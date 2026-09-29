<script setup>
import { ref, watch } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tickets: Object
});

const categories = [
    'Kendala Transaksi QRIS',
    'Integrasi API & Webhook',
    'Penarikan Saldo / Settlement',
    'Pertanyaan Akun & Layanan',
    'Keluhan lainnya'
];

const form = useForm({
    category: '',
    subject: '',
    description: ''
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

watch(() => form.errors, (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
        Swal.close();
        const firstErrorKey = Object.keys(newErrors)[0];
        let errorMessage = newErrors[firstErrorKey];

        if (errorMessage.includes('at least 10 characters')) {
            errorMessage = 'Isi rincian detail keluhan terlalu pendek! Harap tulis minimal 10 karakter.';
        }

        Swal.fire({
            icon: 'warning',
            title: 'Validasi Form Gagal',
            text: errorMessage,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
        });
    }
}, { deep: true });

const submitTicket = () => {
    Swal.fire({
        title: 'Mengirim Laporan',
        text: 'Sedang mendaftarkan tiket Anda ke antrean Helpdesk QRqu...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    form.post(route('customer.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'Tiket Resmi Diterbitkan',
                text: 'Laporan keluhan Anda telah berhasil dibuat dan masuk antrean Customer Service QRqu.',
                confirmButtonColor: '#10b981',
            });
        },
        onError: () => {
            // Handled by watcher
        }
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Tiket Bantuan & Layanan Pengaduan - QRqu" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Pusat Bantuan & Pengaduan</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Ajukan laporan kendala sistem, pembayaran QRIS, webhook, atau pertanyaan langsung ke Customer Service QRqu.
                </p>
            </div>

            <!-- Form Buat Tiket Baru -->
            <div class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Buat Tiket Aduan Baru</h2>
                        <p class="text-xs text-slate-400">Tim technical support kami akan segera merespons tiket Anda.</p>
                    </div>
                </div>

                <form @submit.prevent="submitTicket" class="space-y-4 max-w-2xl">
                    <!-- Kategori -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Kategori Kendala</label>
                        <select
                            v-model="form.category"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        >
                            <option value="" disabled>-- Pilih Kategori Kendala --</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                        <span v-if="form.errors.category" class="text-[11px] text-red-500 font-bold block">{{ form.errors.category }}</span>
                    </div>

                    <!-- Subject / Topik -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Subjek / Judul Singkat Kendala</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            placeholder="Contoh: Pembayaran invoice INV-xxx status belum berubah atau kendala webhook"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        <span v-if="form.errors.subject" class="text-[11px] text-red-500 font-bold block">{{ form.errors.subject }}</span>
                    </div>

                    <!-- Deskripsi Rincian -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Rincian Informasi Keluhan</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Jelaskan kronologi kendala atau lampirkan nomor transaksi/invoice terkait secara lengkap..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            required
                        ></textarea>
                        <span v-if="form.errors.description" class="text-[11px] text-red-500 font-bold block">{{ form.errors.description }}</span>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 disabled:opacity-50 text-slate-950 font-bold rounded-xl text-xs transition-all shadow-md flex items-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-slate-950" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>{{ form.processing ? 'Mengirim Aduan...' : 'Submit Laporan Tiket' }}</span>
                    </button>
                </form>
            </div>

            <!-- Riwayat Tiket Bantuan -->
            <div class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Riwayat Tiket Bantuan Anda</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 text-slate-400 uppercase font-mono text-[11px]">
                                <th class="py-3 px-3">No. Tiket</th>
                                <th class="py-3 px-3">Kategori & Masalah</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3">Tanggapan Admin</th>
                                <th class="py-3 px-3 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="py-4 px-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ ticket.ticket_number }}
                                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ formatDate(ticket.created_at) }}</div>
                                </td>

                                <td class="py-4 px-3 max-w-sm">
                                    <span class="font-bold text-slate-900 dark:text-white block">{{ ticket.subject }}</span>
                                    <span class="inline-block text-[10px] font-mono text-emerald-600 dark:text-emerald-400/80 bg-emerald-500/10 px-2 py-0.5 rounded-full mt-1">{{ ticket.category }}</span>
                                    <p class="text-[11px] text-slate-400 mt-1 line-clamp-2 italic">"{{ ticket.description }}"</p>
                                </td>

                                <td class="py-4 px-3">
                                    <span :class="{
                                        'bg-blue-500/10 text-blue-500 ring-blue-500/20': ticket.status === 'OPEN',
                                        'bg-amber-500/10 text-amber-500 ring-amber-500/20': ticket.status === 'IN_PROGRESS',
                                        'bg-emerald-500/10 text-emerald-500 ring-emerald-500/20': ticket.status === 'RESOLVED',
                                        'bg-slate-500/10 text-slate-500 ring-slate-500/20': ticket.status === 'CLOSED',
                                    }" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 ring-inset">
                                        {{ ticket.status }}
                                    </span>
                                </td>

                                <td class="py-4 px-3 max-w-xs text-slate-600 dark:text-slate-300">
                                    <div v-if="ticket.admin_reply" class="text-xs bg-slate-50 dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200/60 dark:border-slate-800">
                                        <p class="font-bold text-slate-900 dark:text-white text-[11px] mb-0.5">Tanggapan CS:</p>
                                        <p class="italic text-[11px] text-slate-500 dark:text-slate-400">"{{ ticket.admin_reply }}"</p>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs italic">Menunggu respon CS...</span>
                                </td>

                                <td class="py-4 px-3 text-right">
                                    <Link
                                        :href="route('customer.tickets.show', ticket.id)"
                                        class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-emerald-500 dark:bg-slate-800 dark:hover:bg-emerald-500 text-slate-700 dark:text-slate-200 hover:text-slate-950 dark:hover:text-slate-950 rounded-xl font-bold text-xs transition-all"
                                    >
                                        Buka Percakapan
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="text-center py-10 text-slate-400 italic">
                                    Belum ada riwayat tiket aduan yang Anda buat.
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
    </CustomerLayout>
</template>
