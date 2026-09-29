<template>
    <AdminLayout>
        <template #header>Pengaturan Sistem, API & Mail Gateway</template>

        <div class="space-y-6 max-w-5xl">
            <!-- Navigasi Tab Pengaturan -->
            <div class="flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <button
                    v-for="tab in [
                        { id: 'general', label: 'Umum & Platform', icon: 'platform' },
                        { id: 'api', label: 'Operasional API', icon: 'api' },
                        { id: 'pricing', label: 'Harga & Kuota Bulanan', icon: 'pricing' },
                        { id: 'doku', label: 'DOKU Gateway API', icon: 'doku' },
                        { id: 'mail', label: 'Mail Gateway (SMTP)', icon: 'mail' },
                    ]"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        activeTab === tab.id
                            ? 'bg-indigo-600 text-white shadow-md'
                            : 'bg-white dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Form Utama Pengaturan -->
            <form @submit.prevent="submitSettings" class="space-y-6">

                <!-- TAB 1: UMUM & PLATFORM -->
                <div v-show="activeTab === 'general'" class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Identitas Platform & Lokalisasi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Atur nama aplikasi, mata uang, zona waktu, dan mode pemeliharaan platform.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Aplikasi</label>
                            <input
                                v-model="form.app_name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">URL Domain Aplikasi</label>
                            <input
                                v-model="form.app_url"
                                type="url"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Mata Uang Default</label>
                            <input
                                v-model="form.currency"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Zona Waktu (Timezone)</label>
                            <input
                                v-model="form.timezone"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center space-x-3 cursor-pointer p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800">
                            <input
                                v-model="form.maintenance_mode"
                                type="checkbox"
                                class="w-4 h-4 rounded text-indigo-600 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 focus:ring-indigo-500"
                            />
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">Maintenance Mode</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block">Jika aktif, hanya Administrator yang dapat masuk ke platform.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- TAB 2: OPERASIONAL API -->
                <div v-show="activeTab === 'api'" class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Parameter & Protokol API Gateway</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Atur batas waktu invoice, toleransi header keamanan HMAC, dan kuota retry webhook merchant.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Default Expiration QRIS (Menit)</label>
                            <input
                                v-model.number="form.default_expire_minutes"
                                type="number"
                                required
                                min="5"
                                max="1440"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <span class="text-[11px] text-slate-500 mt-1 block">Waktu berlaku tagihan QRIS sebelum berstatus EXPIRED.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">API Timestamp Tolerance (Detik)</label>
                            <input
                                v-model.number="form.api_timestamp_tolerance"
                                type="number"
                                required
                                min="30"
                                max="1800"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <span class="text-[11px] text-slate-500 mt-1 block">Toleransi jeda waktu header X-QRQU-TIMESTAMP (default: 300s).</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Maksimal Retry Webhook</label>
                            <input
                                v-model.number="form.webhook_max_retries"
                                type="number"
                                required
                                min="1"
                                max="10"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <span class="text-[11px] text-slate-500 mt-1 block">Jumlah percobaan pengiriman ulang webhook ke merchant.</span>
                        </div>
                    </div>

                    <!-- Informasi Inbound Callback DOKU -->
                    <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-500/5 border border-indigo-200 dark:border-indigo-500/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Inbound Webhook URL (Daftarkan ke DOKU Back Office)
                            </span>
                            <button
                                type="button"
                                @click="copyToClipboard(settings.inbound_webhook_url)"
                                class="px-2.5 py-1 text-[11px] font-semibold bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-600/30 dark:hover:bg-indigo-600/40 text-indigo-700 dark:text-indigo-300 rounded-lg transition"
                            >
                                Salin URL
                            </button>
                        </div>
                        <input
                            :value="settings.inbound_webhook_url"
                            readonly
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-300 font-mono focus:outline-none"
                        />
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Salin URL di atas ke menu <strong>Notification URL / Webhook</strong> pada DOKU Back Office merchant Anda.</p>
                    </div>
                </div>

                <!-- TAB 3: HARGA & KUOTA BULANAN -->
                <div v-show="activeTab === 'pricing'" class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Standar Paket & Kuota Bulanan Merchant</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tentukan harga langganan default dan kuota transaksi yang dialokasikan per bulan untuk merchant.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="p-5 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Harga Langganan Bulanan (Rp)</label>
                            <input
                                v-model.number="form.monthly_price"
                                type="number"
                                required
                                min="0"
                                step="5000"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-sm font-black text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 pt-1">
                                Rp {{ Number(form.monthly_price || 0).toLocaleString('id-ID') }} / bulan
                            </div>
                            <span class="text-[11px] text-slate-500 block">Harga standar yang dikenakan ke merchant saat berlangganan paket 30 hari.</span>
                        </div>

                        <div class="p-5 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Kuota Transaksi Bulanan</label>
                            <input
                                v-model.number="form.monthly_quota"
                                type="number"
                                required
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-sm font-black text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 pt-1">
                                {{ Number(form.monthly_quota || 0).toLocaleString('id-ID') }} transaksi / bulan
                            </div>
                            <span class="text-[11px] text-slate-500 block">Batas maksimal kuota pembuatan tagihan QRIS yang diizinkan per bulan.</span>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: DOKU PAYMENT GATEWAY API -->
                <div v-show="activeTab === 'doku'" class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Integrasi DOKU Payment Gateway</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Kredensial dan endpoint koneksi DOKU QRIS (Protokol Arsitektur romei 1).</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="triggerDokuTest"
                                :disabled="testingDoku"
                                class="px-3.5 py-2 text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-600/20 dark:hover:bg-emerald-600/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 rounded-xl transition flex items-center gap-1.5 shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                {{ testingDoku ? 'Menguji Koneksi...' : 'Uji Koneksi DOKU' }}
                            </button>
                            <button
                                type="button"
                                @click="scrollToTestPayment"
                                class="px-3.5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <span>Test Pembayaran</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">DOKU Client ID / Merchant ID</label>
                            <input
                                v-model="form.doku_client_id"
                                type="text"
                                placeholder="Contoh: MCH-123456789"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Environment DOKU</label>
                            <select
                                v-model="form.doku_environment"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option value="sandbox">Sandbox (Testing / Simulasi)</option>
                                <option value="production">Production (Live)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">DOKU Base URL</label>
                            <input
                                v-model="form.doku_base_url"
                                type="url"
                                placeholder="https://api-sandbox.doku.com"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <div class="flex gap-2 mt-1.5 text-[11px]">
                                <button type="button" @click="form.doku_base_url = 'https://api-sandbox.doku.com'" class="text-indigo-600 dark:text-indigo-400 hover:underline">Set Sandbox</button>
                                <span class="text-slate-400 dark:text-slate-600">|</span>
                                <button type="button" @click="form.doku_base_url = 'https://api.doku.com'" class="text-indigo-600 dark:text-indigo-400 hover:underline">Set Production</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">DOKU Secret Key</label>
                            <input
                                v-model="form.doku_secret_key"
                                type="text"
                                :placeholder="settings.doku_has_secret ? 'Tersimpan (Ketik jika ingin mengubah)' : 'Ketik Secret Key DOKU...'"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <span class="text-[11px] text-slate-500 mt-1 block">Biarkan kosong jika tidak ingin mengubah Secret Key yang ada.</span>
                        </div>
                    </div>

                    <!-- Uji Coba Pembayaran QRIS (Romei Protocol Test) -->
                    <div id="test-payment-section" class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                    ⚡
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Uji Coba Transaksi Pembayaran QRIS (Test Pembayaran)</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Buat invoice langsung ke DOKU untuk menguji pembentukan QRIS, validasi signature, pemindaian QR, dan webhook real-time.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Form Input Test -->
                        <div class="p-5 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-4">
                            <!-- Nominal Preset Pills -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Pilih Nominal Uji Coba (Nominal Test)</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-for="amt in [1000, 2000, 5000, 10000, 50000]"
                                        :key="amt"
                                        type="button"
                                        @click="selectAmount(amt)"
                                        :class="[
                                            'px-3 py-1.5 rounded-xl text-xs font-bold transition border',
                                            testAmount === amt
                                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-600/30'
                                                : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:border-indigo-400'
                                        ]"
                                    >
                                        Rp {{ amt.toLocaleString('id-ID') }}
                                        <span v-if="amt === 1000" class="ml-1 text-[10px] font-normal opacity-80">(Rekomendasi Live)</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nominal Custom (Rp)</label>
                                    <input
                                        v-model.number="testAmount"
                                        type="number"
                                        min="1000"
                                        step="500"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs font-mono font-bold text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Pelanggan Tester</label>
                                    <input
                                        v-model="testCustomerName"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-200/80 dark:border-slate-800">
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    Total Tagihan Test: <strong class="text-slate-900 dark:text-white font-mono text-sm">Rp {{ Number(testAmount || 0).toLocaleString('id-ID') }}</strong>
                                </span>
                                <button
                                    type="button"
                                    @click="createTestPayment"
                                    :disabled="creatingTestPayment"
                                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-md shadow-indigo-600/20 flex items-center gap-2"
                                >
                                    <svg v-if="creatingTestPayment" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>{{ creatingTestPayment ? 'Menghubungi DOKU...' : 'Generate Tagihan & QRIS Test' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Error Alert if DOKU rejects -->
                        <div v-if="testPaymentError" class="p-4 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-2xl text-xs text-rose-700 dark:text-rose-300 space-y-1">
                            <div class="font-bold flex items-center space-x-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Gagal Membuat Tagihan QRIS:</span>
                            </div>
                            <p class="font-mono">{{ testPaymentError }}</p>
                        </div>

                        <!-- Hasil Uji Coba Pembayaran (QRIS Display Box) -->
                        <div v-if="testPaymentData" class="p-6 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-md space-y-6">
                            <!-- Status Bar -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl" :class="testPaymentData.status === 'PAID' ? 'bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300' : 'bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-300'">
                                <div class="flex items-center space-x-2.5">
                                    <span v-if="testPaymentData.status === 'PAID'" class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                    <span v-else class="w-3 h-3 rounded-full bg-amber-500 animate-ping"></span>
                                    <div>
                                        <div class="font-bold text-xs uppercase tracking-wide">
                                            {{ testPaymentData.status === 'PAID' ? 'Pembayaran Berhasil Diterima (Lunas)' : 'Menunggu Pembayaran QRIS' }}
                                        </div>
                                        <div class="text-[11px] opacity-80">
                                            {{ testPaymentData.status === 'PAID' ? 'Transaksi telah tervalidasi dan tercatat di database.' : 'Scan kode QRIS menggunakan e-wallet atau m-banking.' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="checkStatus"
                                        :disabled="checkingTestStatus"
                                        class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-semibold transition flex items-center gap-1 shadow-sm"
                                    >
                                        <svg class="w-3.5 h-3.5" :class="checkingTestStatus ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>{{ checkingTestStatus ? 'Mengecek...' : 'Cek Status' }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                <!-- QR Box -->
                                <div class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
                                    <div class="w-56 h-56 bg-white p-3 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-center">
                                        <img
                                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=${encodeURIComponent(testPaymentData.qr_string || testPaymentData.qr_url)}`"
                                            alt="QRIS Test"
                                            class="w-full h-full object-contain"
                                        />
                                    </div>
                                    <div class="mt-3 text-center">
                                        <span class="inline-block text-[10px] font-bold text-slate-700 bg-slate-200/80 px-3 py-0.5 rounded-full tracking-wider uppercase">
                                            QRIS Standar Nasional
                                        </span>
                                    </div>
                                </div>

                                <!-- Transaction Details & Actions -->
                                <div class="space-y-4 text-xs">
                                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <div class="py-2.5 flex justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">ID Invoice</span>
                                            <span class="font-mono font-bold text-slate-900 dark:text-white">{{ testPaymentData.invoice_id }}</span>
                                        </div>
                                        <div class="py-2.5 flex justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Total Tagihan</span>
                                            <span class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-sm">{{ testPaymentData.amount_formatted }}</span>
                                        </div>
                                        <div class="py-2.5 flex justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Batas Waktu</span>
                                            <span class="text-slate-700 dark:text-slate-300 font-semibold">⏱ 60 Menit</span>
                                        </div>
                                        <div class="py-2.5 flex justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Status Gateway</span>
                                            <span class="font-bold px-2 py-0.5 rounded text-[11px]" :class="testPaymentData.status === 'PAID' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'">
                                                {{ testPaymentData.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-2 space-y-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <button
                                                type="button"
                                                @click="copyQrString"
                                                class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-xs transition border border-slate-300 dark:border-slate-700 flex items-center justify-center gap-1.5"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                                <span>{{ testCopied ? 'Tersalin!' : 'Salin String QRIS' }}</span>
                                            </button>

                                            <a
                                                :href="testPaymentData.checkout_url"
                                                target="_blank"
                                                class="py-2 px-3 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 font-semibold text-xs transition border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center gap-1.5"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                <span>Buka Checkout</span>
                                            </a>
                                        </div>

                                        <!-- Fast Simulation button -->
                                        <button
                                            v-if="testPaymentData.status !== 'PAID'"
                                            type="button"
                                            @click="simulatePayment"
                                            :disabled="simulatingTestPayment"
                                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span>{{ simulatingTestPayment ? 'Memproses Simulasi...' : '⚡ Simulasi Bayar Lunas (Testing)' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="resetTest"
                                            class="w-full py-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-semibold text-xs transition"
                                        >
                                            Tutup / Buat Uji Coba Baru
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: MAIL GATEWAY (SMTP) -->
                <div v-show="activeTab === 'mail'" class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfigurasi Mail Gateway (SMTP)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pengaturan server email transaksi untuk notifikasi pendaftaran, laporan, dan tagihan invoice.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Mail Driver / Mailer</label>
                            <select
                                v-model="form.mail_mailer"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option value="smtp">SMTP (Direkomendasikan)</option>
                                <option value="sendmail">Sendmail</option>
                                <option value="log">Log (Testing Lokal)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">SMTP Host</label>
                            <input
                                v-model="form.mail_host"
                                type="text"
                                placeholder="Contoh: smtp.gmail.com / smtp.mailgun.org"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">SMTP Port</label>
                            <input
                                v-model.number="form.mail_port"
                                type="number"
                                placeholder="587 / 465 / 2525"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">SMTP Username</label>
                            <input
                                v-model="form.mail_username"
                                type="text"
                                placeholder="Email atau username SMTP"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">SMTP Password</label>
                            <input
                                v-model="form.mail_password"
                                type="password"
                                :placeholder="settings.mail_has_password ? '•••••••••••• (Tersimpan)' : 'Password SMTP...'"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Enkripsi SMTP</label>
                            <select
                                v-model="form.mail_encryption"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option value="tls">TLS (Port 587)</option>
                                <option value="ssl">SSL (Port 465)</option>
                                <option value="none">None (Tanpa Enkripsi)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alamat Email Pengirim (From Address)</label>
                            <input
                                v-model="form.mail_from_address"
                                type="email"
                                placeholder="no-reply@qrqu.id"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Pengirim (From Name)</label>
                            <input
                                v-model="form.mail_from_name"
                                type="text"
                                placeholder="QRqu Payment Gateway"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Panel Uji Coba Pengiriman Email -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Uji Coba Pengiriman Email (Test Email Sender)
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Pastikan Anda telah menekan tombol "Simpan Semua Pengaturan" sebelum mengirim email uji coba.</p>
                        <div class="flex flex-col sm:flex-row gap-2 max-w-lg">
                            <input
                                v-model="testEmailRecipient"
                                type="email"
                                placeholder="Ketik email tujuan uji coba..."
                                class="flex-1 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                            />
                            <button
                                type="button"
                                @click="triggerTestMail"
                                :disabled="sendingTestMail || !testEmailRecipient"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-sm whitespace-nowrap"
                            >
                                {{ sendingTestMail ? 'Mengirim...' : 'Kirim Email Tes' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tombol Simpan Global -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Perubahan akan langsung berlaku pada sistem dan transaksi baru.</span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-md flex items-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Semua Pengaturan' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Romei Exact QRIS Payment Modal -->
        <QrisPaymentModal
            :show="showPaymentModal"
            :invoice-id="testPaymentData?.invoice_id"
            :amount="testPaymentData?.amount"
            :qr-string="testPaymentData?.qr_string"
            :qr-url="testPaymentData?.qr_url"
            :nmid="testPaymentData?.nmid"
            :checkout-url="testPaymentData?.checkout_url"
            :status="testPaymentData?.status"
            @close="showPaymentModal = false"
            @status-updated="(status) => { if (testPaymentData) testPaymentData.status = status; }"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QrisPaymentModal from '@/Components/QrisPaymentModal.vue';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('general');
const testEmailRecipient = ref('');
const sendingTestMail = ref(false);
const testingDoku = ref(false);

// Test Pembayaran QRIS State
const showPaymentModal = ref(false);
const testAmount = ref(1000);
const testCustomerName = ref('Admin Tester');
const creatingTestPayment = ref(false);
const checkingTestStatus = ref(false);
const simulatingTestPayment = ref(false);
const testPaymentData = ref(null);
const testPaymentError = ref(null);
const testCopied = ref(false);
let testPollingTimer = null;

const form = useForm({
    // General
    app_name: props.settings?.app_name || 'QRqu',
    app_url: props.settings?.app_url || 'https://qrqu.id',
    timezone: props.settings?.timezone || 'Asia/Jakarta',
    currency: props.settings?.currency || 'IDR',
    maintenance_mode: Boolean(props.settings?.maintenance_mode),

    // API & Operations
    default_expire_minutes: props.settings?.default_expire_minutes || 60,
    api_timestamp_tolerance: props.settings?.api_timestamp_tolerance || 300,
    webhook_max_retries: props.settings?.webhook_max_retries || 4,

    // Pricing & Quota
    monthly_price: props.settings?.monthly_price || 150000,
    monthly_quota: props.settings?.monthly_quota || 1000,

    // DOKU API
    doku_client_id: props.settings?.doku_client_id || '',
    doku_secret_key: '',
    doku_base_url: props.settings?.doku_base_url || 'https://api-sandbox.doku.com',
    doku_environment: props.settings?.doku_environment || 'sandbox',

    // Mail Gateway
    mail_mailer: props.settings?.mail_mailer || 'smtp',
    mail_host: props.settings?.mail_host || 'smtp.mailtrap.io',
    mail_port: props.settings?.mail_port || 587,
    mail_username: props.settings?.mail_username || '',
    mail_password: '',
    mail_encryption: props.settings?.mail_encryption || 'tls',
    mail_from_address: props.settings?.mail_from_address || 'no-reply@qrqu.id',
    mail_from_name: props.settings?.mail_from_name || 'QRqu Payment Gateway',
});

const submitSettings = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};

const triggerTestMail = () => {
    if (!testEmailRecipient.value) return;
    sendingTestMail.value = true;
    router.post(route('admin.settings.test-mail'), {
        test_email: testEmailRecipient.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            sendingTestMail.value = false;
        },
    });
};

const triggerDokuTest = () => {
    testingDoku.value = true;
    router.post(route('admin.settings.test-doku'), {}, {
        preserveScroll: true,
        onFinish: () => {
            testingDoku.value = false;
        },
    });
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('URL Webhook berhasil disalin ke clipboard!');
};

const scrollToTestPayment = () => {
    activeTab.value = 'doku';
    setTimeout(() => {
        const el = document.getElementById('test-payment-section');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
};

const selectAmount = (val) => {
    testAmount.value = val;
};

const createTestPayment = async () => {
    creatingTestPayment.value = true;
    testPaymentError.value = null;
    testPaymentData.value = null;
    if (testPollingTimer) clearInterval(testPollingTimer);

    try {
        const response = await axios.post(route('admin.settings.test-payment'), {
            amount: testAmount.value,
            customer_name: testCustomerName.value,
        });

        if (response.data.success) {
            testPaymentData.value = response.data;
            showPaymentModal.value = true;
            startTestPolling();
        } else {
            testPaymentError.value = response.data.message || 'Gagal membuat QRIS test.';
        }
    } catch (err) {
        testPaymentError.value = err.response?.data?.message || err.message || 'Terjadi kesalahan saat menghubungi DOKU API.';
    } finally {
        creatingTestPayment.value = false;
    }
};

const checkStatus = async () => {
    if (!testPaymentData.value?.invoice_id) return;
    checkingTestStatus.value = true;
    try {
        const response = await axios.get(route('admin.settings.test-payment.status', { invoice: testPaymentData.value.invoice_id }));
        if (response.data.success) {
            testPaymentData.value.status = response.data.status;
            if (response.data.is_paid) {
                if (testPollingTimer) clearInterval(testPollingTimer);
            }
        }
    } catch (err) {
        console.error(err);
    } finally {
        checkingTestStatus.value = false;
    }
};

const simulatePayment = async () => {
    if (!testPaymentData.value?.invoice_id) return;
    simulatingTestPayment.value = true;
    try {
        const response = await axios.post(route('admin.settings.test-payment.simulate', { invoice: testPaymentData.value.invoice_id }));
        if (response.data.success) {
            testPaymentData.value.status = 'PAID';
            if (testPollingTimer) clearInterval(testPollingTimer);
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Gagal simulasi');
    } finally {
        simulatingTestPayment.value = false;
    }
};

const startTestPolling = () => {
    if (testPollingTimer) clearInterval(testPollingTimer);
    testPollingTimer = setInterval(async () => {
        if (!testPaymentData.value || testPaymentData.value.status === 'PAID') {
            clearInterval(testPollingTimer);
            return;
        }
        try {
            const response = await axios.get(route('admin.settings.test-payment.status', { invoice: testPaymentData.value.invoice_id }));
            if (response.data.success && response.data.is_paid) {
                testPaymentData.value.status = 'PAID';
                clearInterval(testPollingTimer);
            }
        } catch (e) {
            // silent poll
        }
    }, 3000);
};

const copyQrString = () => {
    if (!testPaymentData.value?.qr_string) return;
    navigator.clipboard.writeText(testPaymentData.value.qr_string);
    testCopied.value = true;
    setTimeout(() => {
        testCopied.value = false;
    }, 2000);
};

const resetTest = () => {
    if (testPollingTimer) clearInterval(testPollingTimer);
    testPaymentData.value = null;
    testPaymentError.value = null;
};

onUnmounted(() => {
    if (testPollingTimer) clearInterval(testPollingTimer);
});
</script>
