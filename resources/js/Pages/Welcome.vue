<script setup>
import { ref } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import BankLogo from '@/Components/BankLogo.vue';

const quickEmail = ref('');

const handleQuickStart = () => {
    if (quickEmail.value && quickEmail.value.includes('@')) {
        router.get(route('register'), { email: quickEmail.value });
    } else {
        router.get(route('register'));
    }
};

const bankPartners = [
    { name: 'BCA', code: 'bca' },
    { name: 'Mandiri', code: 'mandiri' },
    { name: 'BRI', code: 'bri' },
    { name: 'BNI', code: 'bni' },
    { name: 'BSI', code: 'bsi' },
    { name: 'QRIS', code: 'qris' },
    { name: 'GoPay', code: 'gopay' },
    { name: 'OVO', code: 'ovo' },
    { name: 'DANA', code: 'dana' },
    { name: 'ShopeePay', code: 'shopeepay' },
];
</script>

<template>
    <Head title="QRqu - Modern QRIS Payment Gateway" />

    <div class="min-h-screen bg-[#FAF8F5] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 antialiased selection:bg-orange-500 selection:text-white transition-colors duration-300 relative overflow-hidden font-sans">
        
        <!-- Ambient Atmosphere Gradient Glows (Bauhaus / Warm Sunrise Aesthetic) -->
        <div class="pointer-events-none absolute -top-40 -left-40 w-[550px] h-[550px] bg-gradient-to-br from-orange-400/20 via-pink-400/15 to-transparent rounded-full blur-3xl dark:from-orange-500/10 dark:via-purple-500/10"></div>
        <div class="pointer-events-none absolute top-1/3 -right-40 w-[600px] h-[600px] bg-gradient-to-bl from-teal-400/20 via-emerald-400/15 to-transparent rounded-full blur-3xl dark:from-emerald-500/10 dark:via-cyan-500/10"></div>
        <div class="pointer-events-none absolute bottom-10 left-1/4 w-[500px] h-[500px] bg-gradient-to-tr from-amber-300/15 via-orange-300/10 to-transparent rounded-full blur-3xl dark:from-indigo-500/10"></div>

        <!-- Geometric Canvas Container (Framed Paper Poster Feel) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/80 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/60 overflow-hidden transition-all duration-300">
                
                <!-- 1. Minimalist Geometric Navbar -->
                <header class="border-b border-slate-100 dark:border-slate-800/80 px-6 sm:px-10 h-20 flex items-center justify-between">
                    <!-- Brand with geometric accent -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-slate-950 dark:bg-white flex items-center justify-center shadow-md">
                            <span class="font-black text-xl text-white dark:text-slate-950 font-mono">Q</span>
                        </div>
                        <div class="flex items-baseline">
                            <span class="font-black text-2xl tracking-tighter text-slate-950 dark:text-white">QRqu</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500 ml-1"></span>
                        </div>
                    </div>

                    <!-- Center Navigation Links (Desktop) -->
                    <nav class="hidden md:flex items-center space-x-8 text-xs font-bold text-slate-600 dark:text-slate-300">
                        <a href="#fitur" class="hover:text-slate-950 dark:hover:text-white transition-colors">Fitur</a>
                        <a href="#arsitektur" class="hover:text-slate-950 dark:hover:text-white transition-colors">Alur Sistem</a>
                        <a href="#harga" class="hover:text-slate-950 dark:hover:text-white transition-colors">Harga Paket</a>
                        <Link :href="route('customer.docs.index')" class="hover:text-slate-950 dark:hover:text-white transition-colors flex items-center gap-1">
                            <span>Dokumentasi</span>
                            <span class="text-[10px] text-orange-500 font-mono">↗</span>
                        </Link>
                    </nav>

                    <!-- Right Controls & Auth -->
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <ThemeToggle />

                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="$page.props.auth.user.is_admin ? route('admin.dashboard') : route('customer.dashboard')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs tracking-tight transition shadow-sm active:scale-95"
                            >
                                <span>Buka Dashboard</span>
                                <span>↗</span>
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white transition px-3 py-2"
                            >
                                Masuk
                            </Link>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-950 font-bold text-xs tracking-tight transition shadow-sm active:scale-95"
                            >
                                <span>Mulai Sekarang</span>
                                <span>→</span>
                            </Link>
                        </template>
                    </div>
                </header>

                <!-- 2. Hero Section: Playful Geometric Typography (Centered Bauhaus Style) -->
                <section class="px-6 sm:px-12 lg:px-16 pt-12 pb-16 lg:pt-20 lg:pb-24 text-center">
                    <div class="max-w-4xl mx-auto space-y-8">
                        <!-- Pill Tag -->
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 text-[11px] font-bold text-slate-700 dark:text-slate-300 tracking-wide font-mono uppercase shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>QRIS PAYMENT GATEWAY ENGINE • LIVE MULTI-TENANT</span>
                        </div>

                        <!-- Big Playful Geometric Headline -->
                        <h1 class="text-4xl sm:text-6xl md:text-7xl font-black text-slate-950 dark:text-white tracking-tight leading-[1.1]">
                            <span class="inline-flex items-center justify-center flex-wrap gap-2 sm:gap-3">
                                <span class="inline-flex items-center justify-center w-10 h-10 sm:w-14 sm:h-14 rounded-full bg-orange-500 text-white shadow-lg shadow-orange-500/30">
                                    <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </span>
                                <span>build</span>
                                <span class="inline-flex items-center justify-center w-8 h-8 sm:w-12 sm:h-12 rounded-2xl bg-teal-500 text-slate-950 font-mono text-sm sm:text-xl font-black">↗</span>
                                <span class="w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-blue-500"></span>
                                <span class="text-slate-400 font-mono text-xl sm:text-2xl">×</span>
                                <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-emerald-400"></span>
                            </span>
                            <br />
                            <span>beautiful</span>
                            <span class="inline-flex items-center ml-2 px-3.5 py-1 rounded-full bg-amber-400 text-slate-950 text-xs sm:text-sm font-mono align-middle font-bold">
                                ● ── o
                            </span>
                            <br />
                            <span class="inline-flex items-center justify-center flex-wrap gap-2 sm:gap-3">
                                <span class="w-8 h-8 sm:w-11 sm:h-11 rounded-t-full bg-purple-500 inline-block -rotate-90"></span>
                                <span class="w-8 h-8 sm:w-11 sm:h-11 rounded-t-full bg-emerald-500 inline-block -rotate-90"></span>
                                <span>payments</span>
                            </span>
                            <br />
                            <span class="underline decoration-slate-950 dark:decoration-white decoration-4 underline-offset-8">faster.</span>
                            <span class="text-orange-500 font-mono text-2xl sm:text-4xl ml-1">✱</span>
                        </h1>

                        <!-- Micro-label and Descriptive Paragraph -->
                        <div class="space-y-3 max-w-2xl mx-auto">
                            <p class="text-xs font-mono text-slate-400 uppercase tracking-widest font-semibold">
                                [ ENTERPRISE QRIS PAYMENT PLATFORM ]
                            </p>
                            <p class="text-sm sm:text-base md:text-lg text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                                QRqu adalah gateway pembayaran QRIS siap pakai. Dapatkan API Key, buat tagihan instan secara otomatis, dan terima webhook pembayaran real-time langsung ke sistem Anda.
                            </p>
                        </div>

                        <!-- Interactive Pill Action Bar (Input + Button) -->
                        <form @submit.prevent="handleQuickStart" class="pt-2 max-w-lg mx-auto w-full">
                            <div class="relative flex items-center bg-slate-100 dark:bg-slate-800/90 rounded-full p-2 border border-slate-200 dark:border-slate-700/80 shadow-inner">
                                <input
                                    v-model="quickEmail"
                                    type="email"
                                    placeholder="Masukkan email merchant Anda..."
                                    class="w-full bg-transparent border-none focus:outline-none focus:ring-0 text-xs sm:text-sm px-4 text-slate-900 dark:text-white placeholder-slate-400 font-medium"
                                />
                                <button
                                    type="submit"
                                    class="px-6 py-3 rounded-full bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-950 font-bold text-xs tracking-tight shrink-0 transition shadow-md active:scale-95 flex items-center gap-2"
                                >
                                    <span>Daftar Gratis</span>
                                    <span>➔</span>
                                </button>
                            </div>
                        </form>

                        <!-- Secondary Action Link -->
                        <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-bold pt-1">
                            <a
                                :href="route('checkout.show', 'INV-20260929-DEMO001')"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-slate-900 dark:text-white hover:text-orange-500 dark:hover:text-orange-400 transition-colors"
                            >
                                <span class="w-6 h-6 rounded-full bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center font-bold">⚡</span>
                                <span>Coba Demo Scan QRIS</span>
                                <span class="font-mono">→</span>
                            </a>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <a href="#fitur" class="text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                                Jelajahi Fitur Unggulan
                            </a>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <a href="#arsitektur" class="text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                                Panduan Integrasi API
                            </a>
                        </div>
                    </div>
                </section>

                <!-- 3. Geometric Partner Ecosystem Strip (Brand Logos) -->
                <section class="border-t border-b border-slate-100 dark:border-slate-800/80 py-8 px-6 sm:px-10 bg-slate-50/50 dark:bg-slate-950/40">
                    <p class="text-center text-[11px] font-bold uppercase tracking-widest text-slate-400 font-mono mb-6">
                        Kompatibel Penuh Dengan Seluruh Bank & Dompet Digital Nasional
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 max-w-5xl mx-auto">
                        <div
                            v-for="bank in bankPartners"
                            :key="bank.code"
                            class="p-2 sm:px-4 sm:py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition flex items-center justify-center"
                        >
                            <BankLogo :name="bank.code" />
                        </div>
                    </div>
                </section>

                <!-- 4. 3-Card Geometric Feature Grid (Image 2 style) -->
                <section id="fitur" class="px-6 sm:px-12 lg:px-16 py-16 lg:py-20 border-b border-slate-100 dark:border-slate-800/80">
                    <div class="text-center max-w-2xl mx-auto mb-14 space-y-2">
                        <span class="text-xs font-mono font-bold uppercase tracking-widest text-orange-500">FITUR UTAMA</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-950 dark:text-white tracking-tight">
                            Semua Kebutuhan Pembayaran, Terintegrasi.
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Dibangun khusus untuk developer dan pebisnis yang membutuhkan solusi pembayaran handal dan siap pakai.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Feature 1: Orange Circle + Teal Triangle -->
                        <div class="bg-slate-50/60 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 p-8 rounded-3xl space-y-4 hover:border-slate-300 dark:hover:border-slate-700 transition">
                            <div class="w-14 h-14 flex items-center justify-center">
                                <!-- Bespoke Geometric Icon -->
                                <div class="relative w-12 h-12">
                                    <div class="w-8 h-8 rounded-full bg-orange-500 absolute top-0 left-0"></div>
                                    <div class="w-0 h-0 border-l-[16px] border-l-transparent border-r-[16px] border-r-transparent border-b-[28px] border-b-teal-500 absolute bottom-0 right-0"></div>
                                </div>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Integrasi API Seketika</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                Endpoint RESTful lengkap dengan otentikasi signature HMAC-SHA256, dokumentasi interaktif, dan library siap pakai untuk berbagai framework.
                            </p>
                            <div class="pt-2 text-xs font-mono font-bold text-orange-500">
                                ➔ Sandbox & Production Ready
                            </div>
                        </div>

                        <!-- Feature 2: Purple Semicircle + Emerald Arch -->
                        <div class="bg-slate-50/60 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 p-8 rounded-3xl space-y-4 hover:border-slate-300 dark:hover:border-slate-700 transition">
                            <div class="w-14 h-14 flex items-center justify-center">
                                <!-- Bespoke Geometric Icon -->
                                <div class="relative w-12 h-12 flex items-center justify-center gap-1">
                                    <div class="w-6 h-10 rounded-l-full bg-purple-500"></div>
                                    <div class="w-6 h-10 rounded-r-full bg-emerald-500"></div>
                                </div>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">QRIS Dinamis Otomatis</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                Pembuatan string QRIS resmi secara otomatis dengan nominal presisi. Pelanggan cukup scan melalui aplikasi bank atau e-wallet apa pun di Indonesia.
                            </p>
                            <div class="pt-2 text-xs font-mono font-bold text-emerald-500">
                                ➔ Deteksi Lunas Seketika
                            </div>
                        </div>

                        <!-- Feature 3: Yellow Capsule + Blue Triangle -->
                        <div class="bg-slate-50/60 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 p-8 rounded-3xl space-y-4 hover:border-slate-300 dark:hover:border-slate-700 transition">
                            <div class="w-14 h-14 flex items-center justify-center">
                                <!-- Bespoke Geometric Icon -->
                                <div class="relative w-12 h-12 flex items-center justify-center">
                                    <div class="w-10 h-6 rounded-full bg-amber-400 absolute"></div>
                                    <div class="w-6 h-6 rounded-full bg-blue-500 absolute -bottom-1 -right-1"></div>
                                </div>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Webhook Idempotent & Retry</h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                Notifikasi pembayaran otomatis ditembakkan ke URL endpoint Anda dengan payload terenkripsi signature dan sistem antrean retry otomatis jika server Anda sibuk.
                            </p>
                            <div class="pt-2 text-xs font-mono font-bold text-blue-500">
                                ➔ Auto-Retry 5x Antrean
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 5. System Architecture / Flow Section (Numbered Geometric Step Cards) -->
                <section id="arsitektur" class="px-6 sm:px-12 lg:px-16 py-16 lg:py-20 bg-slate-50/50 dark:bg-slate-950/30 border-b border-slate-100 dark:border-slate-800/80">
                    <div class="text-center max-w-xl mx-auto mb-14 space-y-2">
                        <span class="text-xs font-mono font-bold uppercase tracking-widest text-emerald-500">ALUR KERJA</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-950 dark:text-white tracking-tight">
                            Dari Invoice Hingga Webhook Sukses
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Hanya 4 langkah mudah untuk mengaktifkan pembayaran QRIS di sistem bisnis Anda.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 space-y-3 relative shadow-sm">
                            <span class="w-8 h-8 rounded-full bg-slate-950 dark:bg-white text-white dark:text-slate-950 font-black text-xs font-mono flex items-center justify-center">01</span>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Create Invoice</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Server Anda mengirim request pembuatan invoice ke QRqu API dengan payload nominal dan nomor tagihan unik.
                            </p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 space-y-3 relative shadow-sm">
                            <span class="w-8 h-8 rounded-full bg-orange-500 text-white font-black text-xs font-mono flex items-center justify-center">02</span>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Generate QRIS</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                QRqu memproses request ke core payment engine dan mengembalikan QR string & halaman checkout responsif dalam milidetik.
                            </p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 space-y-3 relative shadow-sm">
                            <span class="w-8 h-8 rounded-full bg-teal-500 text-slate-950 font-black text-xs font-mono flex items-center justify-center">03</span>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Customer Scan</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Pelanggan memindai QRIS melalui BCA, Mandiri, OVO, GoPay, atau bank apa pun dengan verifikasi instan real-time.
                            </p>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 space-y-3 relative shadow-sm">
                            <span class="w-8 h-8 rounded-full bg-emerald-500 text-slate-950 font-black text-xs font-mono flex items-center justify-center">04</span>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Webhook Callback</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                QRqu menembak webhook bertanda tangan ke server merchant Anda untuk update status lunas dan aktivasi produk otomatis.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- 6. Pricing Plans (Clean Geometric Bauhaus Cards) -->
                <section id="harga" class="px-6 sm:px-12 lg:px-16 py-16 lg:py-24">
                    <div class="text-center max-w-xl mx-auto mb-14 space-y-2">
                        <span class="text-xs font-mono font-bold uppercase tracking-widest text-purple-500">PAKET BERLANGGANAN</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-950 dark:text-white tracking-tight">
                            Investasi Tepat untuk Skala Bisnis Anda
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Tanpa potongan biaya tersembunyi. Dapatkan akses penuh ke fitur API dan integrasi live instan.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
                        <!-- Starter Plan -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 flex flex-col justify-between hover:border-slate-400 dark:hover:border-slate-700 transition shadow-sm">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase font-mono text-slate-400">Paket 1 Bulan</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                </div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Starter</h3>
                                <div class="flex items-baseline">
                                    <span class="text-3xl sm:text-4xl font-black text-slate-950 dark:text-white font-mono">Rp 150.000</span>
                                    <span class="ml-2 text-xs text-slate-400">/ 30 hari</span>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Cocok untuk startup atau proyek baru yang baru mulai menerima pembayaran QRIS.
                                </p>
                                <ul class="space-y-2.5 text-xs text-slate-700 dark:text-slate-300 pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <li class="flex items-center gap-2"><span>✓</span> Kuota 1.000 Transaksi / Bulan</li>
                                    <li class="flex items-center gap-2"><span>✓</span> Rate Limit 60 RPM</li>
                                    <li class="flex items-center gap-2"><span>✓</span> Sandbox & Production API Key</li>
                                    <li class="flex items-center gap-2"><span>✓</span> Signed Webhook Retries</li>
                                </ul>
                            </div>

                            <div class="pt-8">
                                <Link
                                    :href="route('register')"
                                    class="w-full py-3 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-950 dark:text-white font-bold text-xs transition block text-center border border-slate-200 dark:border-slate-700"
                                >
                                    Pilih Paket Starter
                                </Link>
                            </div>
                        </div>

                        <!-- Business Plan (Featured) -->
                        <div class="bg-slate-950 text-white border-2 border-orange-500 rounded-3xl p-8 flex flex-col justify-between shadow-2xl shadow-orange-500/10 relative">
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-orange-500 text-white text-[10px] font-black px-3.5 py-1 rounded-full uppercase tracking-wider font-mono">
                                PALING POPULER
                            </div>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase font-mono text-orange-400">Paket 3 Bulan</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                                </div>
                                <h3 class="text-2xl font-black text-white">Business</h3>
                                <div class="flex items-baseline">
                                    <span class="text-3xl sm:text-4xl font-black text-white font-mono">Rp 400.000</span>
                                    <span class="ml-2 text-xs text-slate-400">/ 90 hari</span>
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Dirancang untuk bisnis berkembang dengan volume transaksi aktif harian.
                                </p>
                                <ul class="space-y-2.5 text-xs text-slate-200 pt-4 border-t border-slate-800">
                                    <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Kuota 5.000 Transaksi</li>
                                    <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Rate Limit 300 RPM</li>
                                    <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Prioritas Antrean Webhook</li>
                                    <li class="flex items-center gap-2"><span class="text-orange-400">✓</span> Multi IP Whitelist</li>
                                </ul>
                            </div>

                            <div class="pt-8">
                                <Link
                                    :href="route('register')"
                                    class="w-full py-3 rounded-full bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs transition block text-center shadow-lg shadow-orange-500/30"
                                >
                                    Pilih Paket Business
                                </Link>
                            </div>
                        </div>

                        <!-- Enterprise Plan -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-8 flex flex-col justify-between hover:border-slate-400 dark:hover:border-slate-700 transition shadow-sm">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase font-mono text-slate-400">Paket 6 Bulan</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                </div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Enterprise</h3>
                                <div class="flex items-baseline">
                                    <span class="text-3xl sm:text-4xl font-black text-slate-950 dark:text-white font-mono">Rp 750.000</span>
                                    <span class="ml-2 text-xs text-slate-400">/ 180 hari</span>
                                </div>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    Kapasitas tinggi untuk aplikasi e-commerce dan perusahaan skala besar.
                                </p>
                                <ul class="space-y-2.5 text-xs text-slate-700 dark:text-slate-300 pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <li class="flex items-center gap-2"><span>✓</span> Kuota 25.000 Transaksi</li>
                                    <li class="flex items-center gap-2"><span>✓</span> Rate Limit 1.000 RPM</li>
                                    <li class="flex items-center gap-2"><span>✓</span> Dedicated Webhook Worker</li>
                                    <li class="flex items-center gap-2"><span>✓</span> Support Prioritas 24/7</li>
                                </ul>
                            </div>

                            <div class="pt-8">
                                <Link
                                    :href="route('register')"
                                    class="w-full py-3 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-950 dark:text-white font-bold text-xs transition block text-center border border-slate-200 dark:border-slate-700"
                                >
                                    Pilih Paket Enterprise
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 7. Minimalist Geometric Footer -->
                <footer class="border-t border-slate-100 dark:border-slate-800 px-6 sm:px-10 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <div class="flex items-center space-x-2">
                        <span class="font-black text-sm text-slate-900 dark:text-white">QRqu.</span>
                        <span>© {{ new Date().getFullYear() }} Didukung oleh Standar QRIS Nasional & Bank Indonesia.</span>
                    </div>

                    <div class="flex items-center space-x-6 text-[11px] font-mono">
                        <span class="inline-flex items-center gap-1.5 text-emerald-500">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            All systems operational
                        </span>
                        <Link :href="route('customer.docs.index')" class="hover:underline">API Docs</Link>
                        <Link :href="route('login')" class="hover:underline">Login</Link>
                    </div>
                </footer>

            </div>
        </div>
    </div>
</template>