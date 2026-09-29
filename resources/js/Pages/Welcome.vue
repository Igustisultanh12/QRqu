<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-emerald-500 selection:text-slate-950">
        <!-- Navigation Bar -->
        <header class="border-b border-slate-800/80 sticky top-0 z-30 backdrop-blur-md bg-slate-950/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <span class="font-black text-2xl text-slate-950">Q</span>
                    </div>
                    <div>
                        <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-emerald-400 to-teal-200 bg-clip-text text-transparent">QRqu</span>
                        <span class="ml-2 text-xs font-mono uppercase bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/20">Payment Gateway</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="$page.props.auth.user.is_admin ? route('admin.dashboard') : route('customer.dashboard')"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20"
                        >
                            Buka Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-semibold text-slate-300 hover:text-white transition px-3 py-2"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="route('register')"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20"
                        >
                            Daftar Sekarang
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="py-20 lg:py-28 relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(16,185,129,0.15),rgba(255,255,255,0))]"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold mb-8">
                    <span>⚡ Integrasi DOKU QRIS Tercepat & Paling Andal</span>
                </div>
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-tight max-w-4xl mx-auto">
                    QRIS Payment Gateway <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">Multi-Tenant</span> Berbasis DOKU
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    QRqu adalah middleware payment gateway siap pakai. Dapatkan API Key, buat tagihan QRIS otomatis, dan terima webhook pembayaran real-time langsung ke sistem Anda.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        :href="route('register')"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-base transition shadow-xl shadow-emerald-500/25 flex items-center justify-center space-x-2"
                    >
                        <span>Mulai Integrasi Sekarang</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </Link>

                    <a
                        :href="route('checkout.show', 'INV-20260929-DEMO001')"
                        target="_blank"
                        class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 font-bold text-base transition border border-slate-700 flex items-center justify-center space-x-2"
                    >
                        <span>Coba Demo Pembayaran QRIS</span>
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- System Architecture Workflow -->
        <section class="py-16 bg-slate-900/50 border-y border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-xs uppercase tracking-widest text-emerald-400 font-bold">Arsitektur Terpercaya</h2>
                    <p class="text-2xl sm:text-3xl font-extrabold text-white mt-1">Alur Transaksi & Webhook End-to-End</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 relative">
                        <div class="text-xs font-mono font-bold text-emerald-400 mb-2">01. REQUEST</div>
                        <h3 class="text-base font-bold text-white mb-2">Create Invoice API</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Website/App Merchant menembak API QRqu dengan HMAC-SHA256 signature.</p>
                    </div>

                    <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 relative">
                        <div class="text-xs font-mono font-bold text-teal-400 mb-2">02. GENERATE</div>
                        <h3 class="text-base font-bold text-white mb-2">DOKU QRIS Engine</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">QRqu meneruskan ke DOKU Live dan mengembalikan QR string & QRIS URL seketika.</p>
                    </div>

                    <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 relative">
                        <div class="text-xs font-mono font-bold text-cyan-400 mb-2">03. PAYMENT</div>
                        <h3 class="text-base font-bold text-white mb-2">Customer Bayar QRIS</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Pelanggan scan QRIS via BCA, Mandiri, GoPay, OVO, Dana. DOKU mengirim webhook.</p>
                    </div>

                    <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 relative">
                        <div class="text-xs font-mono font-bold text-emerald-400 mb-2">04. WEBHOOK</div>
                        <h3 class="text-base font-bold text-white mb-2">Signed Webhook Callback</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">QRqu memvalidasi idempotency dan menembak webhook bertanda tangan ke server Merchant.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Plans Section -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-xs uppercase tracking-widest text-emerald-400 font-bold">Pilihan Paket Berlangganan</h2>
                    <p class="text-3xl sm:text-4xl font-extrabold text-white mt-2">Investasi Terbaik untuk Ekosistem Bisnis Anda</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Paket 1 Bulan -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="text-sm font-bold text-slate-400 mb-2">Paket 1 Bulan</div>
                            <h3 class="text-2xl font-black text-white">Starter</h3>
                            <div class="mt-4 flex items-baseline">
                                <span class="text-4xl font-extrabold text-white">Rp 150.000</span>
                                <span class="ml-2 text-xs text-slate-400">/ 30 hari</span>
                            </div>
                            <p class="mt-4 text-xs text-slate-400">Cocok untuk startup atau proyek baru yang mulai menerima pembayaran QRIS.</p>
                            <ul class="mt-6 space-y-3 text-xs text-slate-300">
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Kuota 1.000 Transaksi / Bulan</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Rate Limit 60 RPM</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Sandbox & Production API Key</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Signed Webhook Retries</li>
                            </ul>
                        </div>
                        <div class="mt-8">
                            <Link :href="route('register')" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition block text-center border border-slate-700">Pilih Paket Starter</Link>
                        </div>
                    </div>

                    <!-- Paket 3 Bulan (Popular) -->
                    <div class="bg-gradient-to-b from-slate-900 to-slate-950 border-2 border-emerald-500/50 rounded-3xl p-8 flex flex-col justify-between shadow-2xl shadow-emerald-500/10 relative">
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 text-[11px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                            Paling Populer
                        </div>
                        <div>
                            <div class="text-sm font-bold text-emerald-400 mb-2">Paket 3 Bulan</div>
                            <h3 class="text-2xl font-black text-white">Business</h3>
                            <div class="mt-4 flex items-baseline">
                                <span class="text-4xl font-extrabold text-white">Rp 400.000</span>
                                <span class="ml-2 text-xs text-slate-400">/ 90 hari</span>
                            </div>
                            <p class="mt-4 text-xs text-slate-400">Dirancang untuk bisnis berkembang dengan volume transaksi aktif harian.</p>
                            <ul class="mt-6 space-y-3 text-xs text-slate-300">
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Kuota 5.000 Transaksi</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Rate Limit 300 RPM</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Prioritas Antrean Webhook</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Multi IP Whitelist</li>
                            </ul>
                        </div>
                        <div class="mt-8">
                            <Link :href="route('register')" class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition block text-center shadow-lg shadow-emerald-500/20">Pilih Paket Business</Link>
                        </div>
                    </div>

                    <!-- Paket 6 Bulan -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="text-sm font-bold text-slate-400 mb-2">Paket 6 Bulan</div>
                            <h3 class="text-2xl font-black text-white">Enterprise</h3>
                            <div class="mt-4 flex items-baseline">
                                <span class="text-4xl font-extrabold text-white">Rp 750.000</span>
                                <span class="ml-2 text-xs text-slate-400">/ 180 hari</span>
                            </div>
                            <p class="mt-4 text-xs text-slate-400">Kapasitas tinggi untuk perusahaan dan aplikasi e-commerce skala besar.</p>
                            <ul class="mt-6 space-y-3 text-xs text-slate-300">
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Kuota 25.000 Transaksi</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Rate Limit 1.000 RPM</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Dedicated Webhook Worker</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Support Prioritas 24/7</li>
                            </ul>
                        </div>
                        <div class="mt-8">
                            <Link :href="route('register')" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition block text-center border border-slate-700">Pilih Paket Enterprise</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-800/80 py-10 bg-slate-950 text-center text-xs text-slate-500">
            <p>© {{ new Date().getFullYear() }} QRqu Gateway. Didukung oleh DOKU Payment Processor. Hak Cipta Dilindungi.</p>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>