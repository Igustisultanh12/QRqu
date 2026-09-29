<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    ticket: Object,
});

const replyForm = useForm({
    message: '',
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

const sendReply = () => {
    replyForm.post(route('customer.tickets.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Tanggapan Terkirim',
                text: 'Pesan balasan Anda telah diteruskan ke Customer Service.',
                confirmButtonColor: '#10b981',
                timer: 2000,
            });
        },
    });
};

const closeTicket = () => {
    Swal.fire({
        title: 'Tutup Tiket Bantuan?',
        text: 'Apakah kendala Anda sudah terselesaikan dengan baik?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Tutup Tiket',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            replyForm.post(route('customer.tickets.close', props.ticket.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Tiket Ditutup', 'Tiket bantuan telah resmi ditutup.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <CustomerLayout>
        <Head :title="`Tiket #${ticket.ticket_number} - QRqu`" />

        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Back & Header -->
            <div class="flex items-center justify-between">
                <Link
                    :href="route('customer.tickets.index')"
                    class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-emerald-500 transition-colors gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Riwayat Tiket</span>
                </Link>

                <button
                    v-if="ticket.status !== 'CLOSED'"
                    @click="closeTicket"
                    type="button"
                    class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors"
                >
                    Tutup Tiket Mandiri
                </button>
            </div>

            <!-- Ticket Card Header -->
            <div class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100 dark:border-slate-800/80">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-base text-emerald-600 dark:text-emerald-400">{{ ticket.ticket_number }}</span>
                            <span :class="{
                                'bg-blue-500/10 text-blue-500 ring-blue-500/20': ticket.status === 'OPEN',
                                'bg-amber-500/10 text-amber-500 ring-amber-500/20': ticket.status === 'IN_PROGRESS',
                                'bg-emerald-500/10 text-emerald-500 ring-emerald-500/20': ticket.status === 'RESOLVED',
                                'bg-slate-500/10 text-slate-500 ring-slate-500/20': ticket.status === 'CLOSED',
                            }" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 ring-inset">
                                {{ ticket.status }}
                            </span>
                        </div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white mt-1">{{ ticket.subject }}</h2>
                    </div>

                    <div class="text-right">
                        <span class="text-[11px] font-mono text-slate-400 block">{{ formatDate(ticket.created_at) }}</span>
                        <span class="inline-block text-[10px] font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full mt-1">{{ ticket.category }}</span>
                    </div>
                </div>

                <!-- Initial Issue Description -->
                <div class="space-y-1">
                    <span class="text-xs font-bold text-slate-400 uppercase font-mono tracking-wider">Kronologi Aduan / Deskripsi:</span>
                    <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">
                        {{ ticket.description }}
                    </div>
                </div>
            </div>

            <!-- Thread Conversation List -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white px-2">Percakapan & Balasan Bantuan</h3>

                <!-- Legacy Admin Reply if exists and no replies yet -->
                <div v-if="ticket.admin_reply && ticket.replies.length === 0" class="flex gap-3 items-start p-4 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 rounded-2xl">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                        CS
                    </div>
                    <div class="space-y-1 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400">Customer Service QRqu</span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ formatDate(ticket.updated_at) }}</span>
                        </div>
                        <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ ticket.admin_reply }}</p>
                    </div>
                </div>

                <!-- Conversation replies -->
                <div
                    v-for="reply in ticket.replies"
                    :key="reply.id"
                    :class="[
                        'flex gap-3 items-start p-4 rounded-2xl border',
                        reply.is_admin
                            ? 'bg-indigo-50/50 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900/50'
                            : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800'
                    ]"
                >
                    <div
                        :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0',
                            reply.is_admin ? 'bg-indigo-600 text-white' : 'bg-emerald-600 text-white'
                        ]"
                    >
                        {{ reply.is_admin ? 'CS' : 'Anda' }}
                    </div>
                    <div class="space-y-1 flex-1">
                        <div class="flex items-center justify-between">
                            <span :class="['text-xs font-bold', reply.is_admin ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-900 dark:text-white']">
                                {{ reply.is_admin ? 'Customer Service QRqu' : (reply.user?.name || 'Anda') }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ formatDate(reply.created_at) }}</span>
                        </div>
                        <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ reply.message }}</p>
                    </div>
                </div>

                <div v-if="!ticket.admin_reply && ticket.replies.length === 0" class="text-center py-6 text-slate-400 text-xs italic bg-white dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                    Belum ada balasan dari Operator CS. Tiket Anda sedang dalam antrean.
                </div>
            </div>

            <!-- Reply Box (if ticket not closed) -->
            <div v-if="ticket.status !== 'CLOSED'" class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">Kirim Tanggapan Balasan</h4>
                <form @submit.prevent="sendReply" class="space-y-3">
                    <textarea
                        v-model="replyForm.message"
                        rows="3"
                        placeholder="Tulis pesan lanjutan atau klarifikasi kepada admin di sini..."
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        required
                    ></textarea>

                    <div class="flex items-center justify-end">
                        <button
                            type="submit"
                            :disabled="replyForm.processing"
                            class="px-5 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-slate-950 font-bold rounded-xl text-xs shadow transition-all disabled:opacity-50"
                        >
                            {{ replyForm.processing ? 'Mengirim...' : 'Kirim Balasan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerLayout>
</template>
