<template>
    <div class="min-h-screen bg-[#FAF8F5] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex flex-col items-center justify-center p-3 sm:p-6 antialiased transition-colors duration-300 relative overflow-hidden font-sans">
        <!-- Ambient Atmosphere Gradient Glows -->
        <div class="pointer-events-none absolute -top-24 -left-24 w-80 h-80 bg-gradient-to-br from-orange-400/20 via-pink-400/15 to-transparent rounded-full blur-3xl dark:from-orange-500/10"></div>
        <div class="pointer-events-none absolute -bottom-24 -right-24 w-80 h-80 bg-gradient-to-tl from-emerald-400/20 via-teal-400/15 to-transparent rounded-full blur-3xl dark:from-emerald-500/10"></div>

        <!-- Floating Theme Toggle in Checkout -->
        <div class="absolute top-4 right-4 z-50">
            <ThemeToggle />
        </div>

        <div :class="['w-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 rounded-[2rem] shadow-2xl shadow-slate-200/50 dark:shadow-black/70 overflow-hidden transition-all duration-300 my-auto relative z-10', invoice.doku_url ? 'max-w-xl' : 'max-w-md']">
            <!-- Header -->
            <div class="bg-slate-950 dark:bg-white p-5 sm:p-6 text-center relative overflow-hidden">
                <div class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-white/10 dark:bg-slate-950/10 backdrop-blur border border-white/20 dark:border-slate-950/20 mb-2 shadow-inner">
                    <span class="text-white dark:text-slate-950 font-black text-xl font-mono">Q</span>
                </div>
                <h1 class="text-lg sm:text-xl font-black text-white dark:text-slate-950 tracking-tight">QRqu Gateway</h1>
                <p class="text-xs text-slate-300 dark:text-slate-600 font-medium mt-0.5">Merchant: {{ invoice.merchant_name }}</p>
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

                <!-- State: PENDING (DOKU Live Iframe or QRIS display) -->
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

                    <!-- Real DOKU Hosted QRIS Checkout (Iframe) -->
                    <div v-if="invoice.doku_url" class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-md bg-white">
                        <div class="px-4 py-2 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-[11px] font-mono font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Portal Resmi QRIS DOKU
                            </span>
                            <a
                                :href="invoice.doku_url"
                                target="_blank"
                                class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                            >
                                <span>Buka Tab Baru</span>
                                <span>↗</span>
                            </a>
                        </div>
                        <div class="w-full h-[620px] bg-white relative">
                            <iframe
                                :src="invoice.doku_url"
                                class="w-full h-full border-0"
                                allow="payment; geolocation; microphone; camera font-mono"
                            ></iframe>
                        </div>
                    </div>

                    <!-- Official QRIS DOKU Layout (when not using hosted iframe) -->
                    <div v-else class="space-y-4">
                        <div class="bg-white p-5 rounded-2xl flex flex-col items-center justify-center border-2 border-red-500 shadow-md relative group">
                            <!-- Official QRIS Header -->
                            <div class="w-full pb-3 mb-3 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center space-x-1.5">
                                    <span class="px-2 py-0.5 bg-red-600 text-white font-black text-[10px] tracking-wider rounded font-mono">QRIS</span>
                                    <span class="text-[10px] font-bold text-slate-700">Pembayaran Nasional</span>
                                </div>
                                <span class="text-[9px] font-mono text-slate-400">NMID: ID1020021893601</span>
                            </div>

                            <!-- Merchant Identity on QRIS -->
                            <div class="text-center mb-3">
                                <div class="text-xs font-black text-slate-900 tracking-tight">{{ invoice.merchant_name }}</div>
                                <div class="text-[10px] text-slate-500">QRqu DOKU Payment Gateway</div>
                            </div>

                            <!-- QR Code Box -->
                            <div class="w-52 h-52 sm:w-60 sm:h-60 p-2 border border-slate-200 rounded-xl flex items-center justify-center bg-white shadow-inner">
                                <img
                                    :src="qrCodeImageUrl"
                                    alt="QRIS DOKU Code"
                                    class="w-full h-full object-contain"
                                />
                            </div>

                            <!-- GPN & BI Footer Stamp -->
                            <div class="w-full pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-semibold">
                                <span class="flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    GPN / Gerbang Pembayaran Nasional
                                </span>
                                <span>Dicetak Resmi DOKU</span>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="bg-slate-50 dark:bg-slate-950/80 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-400 space-y-1.5">
                            <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Petunjuk Pembayaran:</span>
                            </div>
                            <ol class="list-decimal list-inside space-y-0.5 pl-1 leading-relaxed text-[10.5px]">
                                <li>Buka aplikasi m-Banking (BCA, Mandiri Livin', BRImo, BNI) atau E-Wallet (GoPay, OVO, Dana, ShopeePay).</li>
                                <li>Pilih menu <strong>Pindai / Scan QRIS</strong> lalu arahkan kamera ke kode QR di atas.</li>
                                <li>Periksa nominal <strong class="text-slate-900 dark:text-white font-mono">{{ invoice.amount_formatted }}</strong> dan konfirmasi PIN Anda.</li>
                                <li>Status pembayaran akan terverifikasi secara otomatis secara realtime.</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-2.5">
                        <button
                            v-if="invoice.qr_string"
                            @click="copyQrString"
                            type="button"
                            class="w-full py-2.5 px-4 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition border border-slate-300 dark:border-slate-700 flex items-center justify-center space-x-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>{{ copied ? 'Tersalin ke Clipboard!' : 'Salin Kode QRIS' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800/60 text-center">
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Aman & Terenkripsi • Standar QRIS Nasional Bank Indonesia
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
