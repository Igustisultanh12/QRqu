<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
        <!-- Backdrop Blur (Romei Exact Match) -->
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <!-- Dialog Box (Romei Exact Match) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg h-[82vh] sm:h-[86vh] flex flex-col shadow-2xl transform transition-all relative z-10 overflow-hidden">
            <!-- Modal Header -->
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2 truncate pr-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0" :class="isPaid ? '' : 'animate-pulse'"></span>
                    <span class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider font-mono truncate">
                        Invoice: {{ invoiceId }}
                    </span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="!isPaid"
                        type="button"
                        @click="simulatePayment"
                        :disabled="simulating"
                        title="Simulasi Bayar Lunas Cepat"
                        class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 transition flex items-center gap-1 disabled:opacity-50"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ simulating ? 'Memproses...' : 'Simulasi Lunas' }}</span>
                    </button>

                    <a
                        v-if="activePaymentUrl"
                        :href="activePaymentUrl"
                        target="_blank"
                        title="Buka Tab Baru"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>

                    <button
                        type="button"
                        @click="closeModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Iframe DOKU Live Production Page) -->
            <div class="flex-1 bg-white relative overflow-hidden">
                <iframe
                    v-if="activePaymentUrl"
                    :src="activePaymentUrl"
                    class="w-full h-full border-0"
                    allow="geolocation; microphone; camera font-mono"
                ></iframe>

                <div v-else class="flex flex-col items-center justify-center h-full p-6 text-center text-slate-500 dark:text-slate-400 space-y-3">
                    <svg class="animate-spin w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <p class="text-xs font-medium">Memuat portal pembayaran QRIS resmi...</p>
                </div>

                <!-- Success Overlay when paid -->
                <div v-if="isPaid" class="absolute inset-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center space-y-3 animate-in fade-in z-20">
                    <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-500 flex items-center justify-center shadow-lg">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Pembayaran Berhasil!</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs">
                        Transaksi <strong class="font-mono text-slate-800 dark:text-slate-200">{{ invoiceId }}</strong> telah sukses dibayar dan diverifikasi secara real-time.
                    </p>
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-md"
                    >
                        Tutup Jendela
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    invoiceId: String,
    amount: [Number, String],
    paymentUrl: String,
    qrUrl: String,
    qrString: String,
    checkoutUrl: String,
    status: {
        type: String,
        default: 'PENDING',
    },
});

const emit = defineEmits(['close', 'status-updated', 'paid']);

const currentStatus = ref(props.status);
const simulating = ref(false);
let pollTimer = null;

const isPaid = computed(() => {
    return currentStatus.value === 'PAID' || currentStatus.value === 'SUCCESS';
});

const activePaymentUrl = computed(() => {
    return props.paymentUrl || props.qrUrl || props.checkoutUrl || '';
});

const closeModal = () => {
    clearChecker();
    emit('close');
};

const clearChecker = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const startCheckingPaymentStatus = () => {
    clearChecker();
    if (!props.invoiceId) return;

    pollTimer = setInterval(async () => {
        if (isPaid.value) {
            clearChecker();
            return;
        }

        try {
            const res = await axios.get(`/api/admin/monitoring/check-status/${props.invoiceId}`);
            if (res.data.status === 'success' || res.data.payment_status === 'SUCCESS' || res.data.is_paid) {
                clearChecker();
                currentStatus.value = 'PAID';
                emit('status-updated', 'PAID');
                emit('paid', props.invoiceId);
            }
        } catch (e) {
            // fallback web endpoint
            try {
                const resWeb = await axios.get(route('admin.settings.test-payment.status', { invoice: props.invoiceId }));
                if (resWeb.data.success && resWeb.data.is_paid) {
                    clearChecker();
                    currentStatus.value = 'PAID';
                    emit('status-updated', 'PAID');
                    emit('paid', props.invoiceId);
                }
            } catch (err2) {}
        }
    }, 3000);
};

const simulatePayment = async () => {
    if (!props.invoiceId) return;
    simulating.value = true;
    try {
        const res = await axios.post(`/api/admin/monitoring/simulate/${props.invoiceId}`);
        if (res.data.status === 'success' || res.data.payment_status === 'SUCCESS') {
            currentStatus.value = 'PAID';
            emit('status-updated', 'PAID');
            emit('paid', props.invoiceId);
        }
    } catch (e) {
        try {
            const resWeb = await axios.post(route('admin.settings.test-payment.simulate', { invoice: props.invoiceId }));
            if (resWeb.data.success) {
                currentStatus.value = 'PAID';
                emit('status-updated', 'PAID');
                emit('paid', props.invoiceId);
            }
        } catch (err2) {
            alert('Gagal melakukan simulasi pembayaran.');
        }
    } finally {
        simulating.value = false;
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        currentStatus.value = props.status;
        startCheckingPaymentStatus();
    } else {
        clearChecker();
    }
});

watch(() => props.status, (newStatus) => {
    currentStatus.value = newStatus;
});

onMounted(() => {
    if (props.show) {
        startCheckingPaymentStatus();
    }
});

onUnmounted(() => {
    clearChecker();
});
</script>
