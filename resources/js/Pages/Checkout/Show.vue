<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col items-center justify-center p-3 sm:p-6 antialiased transition-colors duration-200 relative">
        <!-- Floating Theme Toggle in Checkout -->
        <div class="absolute top-3 right-3 sm:top-4 sm:right-4 z-50">
            <ThemeToggle />
        </div>

        <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl shadow-xl dark:shadow-2xl overflow-hidden transition-colors duration-200 my-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-500 p-5 sm:p-6 text-center relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="inline-flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/20 mb-2 sm:mb-3 shadow-inner">
                    <span class="text-white font-black text-xl sm:text-2xl">Q</span>
                </div>
                <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight">QRqu Payment Gateway</h1>
                <p class="text-xs text-emerald-100 font-medium mt-1">Merchant: {{ invoice.merchant_name }}</p>
            </div>

            <!-- Invoice Details -->
            <div class="p-4 sm:p-6 text-center border-b border-slate-200 dark:border-slate-800">
                <div class="text-[11px] sm:text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold mb-1">Total Pembayaran</div>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ invoice.amount_formatted }}</div>
                <div class="inline-flex items-center space-x-2 text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-2 bg-slate-100 dark:bg-slate-800/80 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700/50 max-w-full">
                    <span class="font-mono">#{{ invoice.id }}</span>
                    <span>•</span>
                    <span class="truncate max-w-[120px] sm:max-w-[180px] font-mono">{{ invoice.external_id }}</span>
                </div>
            </div>

            <!-- Body: States -->
            <div class="p-4 sm:p-6">
                <!-- State: PAID -->
                <div v-if="paymentStatus === 'PAID'" class="text-center py-6 space-y-4">
                    <div class="w-20 h-20 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center mx-auto animate-bounce">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-white">Pembayaran Berhasil!</h2>
                        <p class="text-sm text-slate-400 mt-1">Transaksi Anda telah dikonfirmasi dan tuntas.</p>
                    </div>
                    <div v-if="invoice.callback_url" class="pt-4">
                        <a :href="invoice.callback_url" class="inline-flex items-center justify-center w-full px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold transition shadow-lg shadow-emerald-500/20 text-sm">
                            <span>Kembali ke Merchant</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                </div>

                <!-- State: EXPIRED -->
                <div v-else-if="paymentStatus === 'EXPIRED'" class="text-center py-6 space-y-4">
                    <div class="w-20 h-20 bg-rose-500/20 text-rose-400 border border-rose-500/40 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-white">Invoice Kedaluwarsa</h2>
                        <p class="text-sm text-slate-400 mt-1">Batas waktu pembayaran untuk invoice ini telah habis.</p>
                    </div>
                </div>

                <!-- State: PENDING (QR Code display) -->
                <div v-else class="space-y-6">
                    <!-- Countdown & Status -->
                    <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-950 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Menunggu Pembayaran</span>
                        </div>
                        <div class="text-xs font-mono font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 rounded-lg border border-amber-200 dark:border-amber-500/20">
                            ⏱ {{ countdownDisplay }}
                        </div>
                    </div>

                    <!-- QR Code Display Box -->
                    <div class="bg-white p-4 sm:p-6 rounded-2xl flex flex-col items-center justify-center border border-slate-200 dark:border-transparent shadow-inner relative group">
                        <div class="w-48 h-48 sm:w-56 sm:h-56 flex items-center justify-center bg-white">
                            <!-- Dynamic QR generator image or QR string QR code -->
                            <img
                                :src="qrCodeImageUrl"
                                alt="QRIS QR Code"
                                class="w-full h-full object-contain"
                            />
                        </div>
                        <div class="mt-3 text-center">
                            <span class="inline-block text-[11px] font-bold text-slate-800 tracking-wider uppercase bg-slate-100 px-3 py-0.5 rounded-full border border-slate-200">
                                QRIS STANDAR NASIONAL
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-relaxed">
                        Scan QR menggunakan aplikasi e-wallet (GoPay, OVO, Dana, ShopeePay) atau mobile banking BCA, Mandiri, BRI, BNI.
                    </p>

                    <!-- Actions -->
                    <div class="space-y-2.5">
                        <!-- Direct DOKU Hosted Checkout Button if available -->
                        <a
                            v-if="invoice.doku_url"
                            :href="invoice.doku_url"
                            class="w-full py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 active:scale-95 text-white font-bold text-xs transition shadow-md shadow-rose-600/20 flex items-center justify-center space-x-2"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            <span>Bayar via Portal Resmi DOKU ↗</span>
                        </a>

                        <button
                            v-if="invoice.qr_string"
                            @click="copyQrString"
                            type="button"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-xs transition border border-slate-300 dark:border-slate-700 flex items-center justify-center space-x-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>{{ copied ? 'Tersalin ke Clipboard!' : 'Salin Kode QRIS' }}</span>
                        </button>

                        <!-- Sandbox Simulator Button (For Test Mode) -->
                        <div v-if="invoice.is_sandbox" class="pt-2 border-t border-slate-200 dark:border-slate-800/80">
                            <button
                                @click="simulateSandboxPayment"
                                :disabled="simulating"
                                type="button"
                                class="w-full py-2.5 px-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/30 dark:hover:bg-indigo-600/50 text-indigo-700 dark:text-indigo-300 font-semibold text-xs transition border border-indigo-200 dark:border-indigo-500/40 flex items-center justify-center space-x-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>{{ simulating ? 'Memproses Simulasi...' : '⚡ Simulasi Pembayaran Sukses (Sandbox)' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800/60 text-center">
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Aman & Terenkripsi • Didukung oleh DOKU Payment Gateway
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const paymentStatus = ref(props.invoice.status);
const copied = ref(false);
const simulating = ref(false);

// QR Code Image source (using QR server generator API for sharp visual QRIS code)
const qrCodeImageUrl = computed(() => {
    const rawContent = props.invoice.qr_string || props.invoice.qr_url || window.location.href;
    return `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(rawContent)}`;
});

// Countdown Timer logic
const remainingSeconds = ref(0);
const calculateRemaining = () => {
    if (!props.invoice.expired_at) return 3600;
    const expiry = new Date(props.invoice.expired_at).getTime();
    const now = new Date().getTime();
    return Math.max(0, Math.floor((expiry - now) / 1000));
};

remainingSeconds.value = calculateRemaining();

const countdownDisplay = computed(() => {
    const mins = Math.floor(remainingSeconds.value / 60);
    const secs = remainingSeconds.value % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

let timerInterval = null;
let pollInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value--;
        } else if (paymentStatus.value === 'PENDING') {
            paymentStatus.value = 'EXPIRED';
        }
    }, 1000);

    // Safe Polling every 3 seconds
    pollInterval = setInterval(async () => {
        if (paymentStatus.value !== 'PENDING') {
            clearInterval(pollInterval);
            return;
        }

        try {
            const res = await axios.get(route('checkout.status', props.invoice.id));
            if (res.data && res.data.status) {
                paymentStatus.value = res.data.status;
                if (res.data.is_paid) {
                    clearInterval(pollInterval);
                }
            }
        } catch (e) {
            // Ignore temporary network glitch during polling
        }
    }, 3000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (pollInterval) clearInterval(pollInterval);
});

const copyQrString = () => {
    if (props.invoice.qr_string) {
        navigator.clipboard.writeText(props.invoice.qr_string);
        copied.value = true;
        setTimeout(() => copied.value = false, 2500);
    }
};

const simulateSandboxPayment = async () => {
    if (simulating.value) return;
    simulating.value = true;
    try {
        const res = await axios.post(route('checkout.simulate', props.invoice.id));
        if (res.data.success) {
            paymentStatus.value = 'PAID';
        }
    } catch (e) {
        alert('Gagal mensimulasikan pembayaran.');
    } finally {
        simulating.value = false;
    }
};
</script>
