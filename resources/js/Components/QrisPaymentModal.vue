<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm transition-all duration-200">
        <!-- Modal Dialog Box (Romei Exact Match) -->
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transition-all transform animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header (White Bar with Green Dot & Invoice ID) -->
            <div class="px-5 py-3.5 flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900">
                <div class="flex items-center space-x-2 truncate pr-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0" :class="isPaid ? '' : 'animate-ping'"></span>
                    <span class="font-mono text-xs sm:text-[13px] font-bold text-slate-800 dark:text-slate-100 truncate">
                        INVOICE: {{ invoiceId }}
                    </span>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Complete Payment in Banner (Romei Exact Dark Blue Header) -->
            <div class="bg-[#0b1d3a] dark:bg-[#071326] py-3.5 px-4 text-center text-white border-b border-white/10">
                <div class="text-[12px] font-medium text-slate-300 tracking-wide mb-2">
                    {{ isPaid ? 'Status Transaksi' : 'Complete Payment in' }}
                </div>
                <div v-if="!isPaid" class="flex items-center justify-center space-x-1.5 font-sans">
                    <span class="bg-[#e52528] text-white font-black text-base sm:text-lg px-2.5 py-0.5 rounded-md shadow-inner">
                        {{ formattedMinutes }}
                    </span>
                    <span class="text-xs sm:text-sm text-slate-200 font-semibold px-0.5">Minutes,</span>
                    <span class="bg-[#e52528] text-white font-black text-base sm:text-lg px-2.5 py-0.5 rounded-md shadow-inner">
                        {{ formattedSeconds }}
                    </span>
                    <span class="text-xs sm:text-sm text-slate-200 font-semibold pl-0.5">Seconds</span>
                </div>
                <div v-else class="inline-flex items-center space-x-1.5 bg-emerald-500/20 text-emerald-300 px-3 py-1 rounded-full text-xs font-bold border border-emerald-500/40">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <span>LUNAS / BERHASIL DIBAYAR</span>
                </div>
            </div>

            <!-- Modal Body (White Background with QRIS Display) -->
            <div class="p-6 bg-white dark:bg-slate-900 text-center space-y-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Scan QR Code to Pay
                </h3>

                <!-- QRIS Official Logo -->
                <div class="flex items-center justify-center pt-1 pb-1">
                    <svg class="h-9 w-auto text-slate-950 dark:text-white" viewBox="0 0 160 45" fill="currentColor">
                        <path d="M22.5 0C10.1 0 0 10.1 0 22.5S10.1 45 22.5 45c4.7 0 9.1-1.5 12.8-4l5.1 5.1 5.6-5.6-5.1-5.1C43.5 31.6 45 27.2 45 22.5 45 10.1 34.9 0 22.5 0zm0 7.8c8.1 0 14.7 6.6 14.7 14.7S30.6 37.2 22.5 37.2 7.8 30.6 7.8 22.5 14.4 7.8 22.5 7.8z"/>
                        <rect x="17.5" y="17.5" width="10" height="10" rx="2"/>
                        <path d="M54 4h18c8.8 0 16 7.2 16 16 0 5.4-2.7 10.2-6.8 13.1l7.8 12.9h-9.8l-6.8-11.5H64v11.5H54V4zm10 7.5v11.5h8c4.7 0 8.5-3.8 8.5-8.5s-3.8-8.5-8.5-8.5h-8z"/>
                        <rect x="94" y="4" width="9" height="42" rx="1.5"/>
                        <path d="M141 4h-24v8.5h16c3.6 0 6.5 2.9 6.5 6.5s-2.9 6.5-6.5 6.5h-16v20.5h25v-8.5h-16c-3.6 0-6.5-2.9-6.5-6.5s2.9-6.5 6.5-6.5h15V4z"/>
                    </svg>
                </div>

                <!-- QR Image Box -->
                <div class="relative inline-block mx-auto bg-white p-3 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                    <img
                        :src="qrImageUrl"
                        alt="QRIS QR Code"
                        class="w-60 h-60 object-contain mx-auto transition-transform"
                    />

                    <!-- Success Overlay if Paid -->
                    <div v-if="isPaid" class="absolute inset-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center p-4 space-y-2 animate-in fade-in">
                        <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h4 class="text-base font-extrabold text-emerald-600 dark:text-emerald-400">Pembayaran Berhasil!</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tagihan telah lunas diverifikasi sistem.</p>
                    </div>
                </div>

                <!-- NMID text (Romei Style) -->
                <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 tracking-wider pt-1">
                    NMID: {{ nmid || 'ID1026478551298' }}
                </div>

                <!-- Red Bottom Bar (Romei Authentic QRIS Strip) -->
                <div class="h-3 bg-[#e52528] w-full rounded-full mt-2 shadow-sm"></div>

                <!-- Details & Actions Footer -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs space-y-3">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                        <span>Total Pembayaran:</span>
                        <span class="font-black text-indigo-600 dark:text-indigo-400 font-mono text-sm">
                            Rp {{ Number(amount || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <!-- Quick buttons -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button
                            type="button"
                            @click="copyQrString"
                            class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-xs transition border border-slate-300 dark:border-slate-700 flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>{{ copied ? 'Tersalin!' : 'Salin String QRIS' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="checkStatus"
                            :disabled="checking"
                            class="py-2 px-3 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 font-semibold text-xs transition border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" :class="checking ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>{{ checking ? 'Mengecek...' : 'Cek Status' }}</span>
                        </button>
                    </div>

                    <!-- Simulate Button -->
                    <button
                        v-if="!isPaid"
                        type="button"
                        @click="simulatePayment"
                        :disabled="simulating"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ simulating ? 'Memproses Simulasi...' : '⚡ Simulasi Bayar Lunas (Testing)' }}</span>
                    </button>

                    <a
                        v-if="checkoutUrl"
                        :href="checkoutUrl"
                        target="_blank"
                        class="block text-center text-[11px] text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 pt-1"
                    >
                        Buka Halaman Checkout Publik →
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    invoiceId: String,
    amount: [Number, String],
    qrString: String,
    qrUrl: String,
    nmid: String,
    checkoutUrl: String,
    status: {
        type: String,
        default: 'PENDING',
    },
});

const emit = defineEmits(['close', 'status-updated']);

const currentStatus = ref(props.status);
const timeLeft = ref(60 * 60 - 7); // ~59m 53s
const copied = ref(false);
const checking = ref(false);
const simulating = ref(false);
let timerInterval = null;
let pollInterval = null;

const isPaid = computed(() => currentStatus.value === 'PAID');

const formattedMinutes = computed(() => {
    const mins = Math.floor(timeLeft.value / 60);
    return String(mins).padStart(2, '0');
});

const formattedSeconds = computed(() => {
    const secs = timeLeft.value % 60;
    return String(secs).padStart(2, '0');
});

const qrImageUrl = computed(() => {
    const rawContent = props.qrString || props.qrUrl || props.checkoutUrl || 'https://qrqu.id';
    return `https://api.qrserver.com/v1/create-qr-code/?size=350x350&margin=8&data=${encodeURIComponent(rawContent)}`;
});

const closeModal = () => {
    emit('close');
};

const copyQrString = () => {
    if (!props.qrString) return;
    navigator.clipboard.writeText(props.qrString);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const checkStatus = async () => {
    if (!props.invoiceId) return;
    checking.value = true;
    try {
        const response = await axios.get(route('admin.settings.test-payment.status', { invoice: props.invoiceId }));
        if (response.data.success) {
            currentStatus.value = response.data.status;
            emit('status-updated', response.data.status);
        }
    } catch (err) {
        console.error(err);
    } finally {
        checking.value = false;
    }
};

const simulatePayment = async () => {
    if (!props.invoiceId) return;
    simulating.value = true;
    try {
        const response = await axios.post(route('admin.settings.test-payment.simulate', { invoice: props.invoiceId }));
        if (response.data.success) {
            currentStatus.value = 'PAID';
            emit('status-updated', 'PAID');
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Gagal simulasi');
    } finally {
        simulating.value = false;
    }
};

const startTimers = () => {
    clearInterval(timerInterval);
    clearInterval(pollInterval);

    timeLeft.value = 60 * 60 - 7;
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        }
    }, 1000);

    pollInterval = setInterval(async () => {
        if (!props.invoiceId || isPaid.value) {
            clearInterval(pollInterval);
            return;
        }
        try {
            const response = await axios.get(route('admin.settings.test-payment.status', { invoice: props.invoiceId }));
            if (response.data.success && response.data.is_paid) {
                currentStatus.value = 'PAID';
                emit('status-updated', 'PAID');
                clearInterval(pollInterval);
            }
        } catch (e) {
            // silent poll
        }
    }, 3000);
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        currentStatus.value = props.status;
        startTimers();
    } else {
        clearInterval(timerInterval);
        clearInterval(pollInterval);
    }
});

onMounted(() => {
    if (props.show) {
        startTimers();
    }
});

onUnmounted(() => {
    clearInterval(timerInterval);
    clearInterval(pollInterval);
});
</script>
