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
                        { id: 'doku', label: 'Gateway API', icon: 'doku' },
                        { id: 'mail', label: 'Mail Gateway (SMTP)', icon: 'mail' },
                    ]"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-2',
                        activeTab === tab.id
                            ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950 shadow-md'
                            : 'bg-white/80 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Form Utama Pengaturan -->
            <form @submit.prevent="submitSettings" class="space-y-6">

                <!-- TAB 1: UMUM & PLATFORM -->
                <div v-show="activeTab === 'general'" class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
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
                <div v-show="activeTab === 'api'" class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
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
                <div v-show="activeTab === 'pricing'" class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
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
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-slate-300 dark:border-slate-700 text-sm font-black text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
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
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-slate-300 dark:border-slate-700 text-sm font-black text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                            <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400 pt-1">
                                {{ Number(form.monthly_quota || 0).toLocaleString('id-ID') }} transaksi / bulan
                            </div>
                            <span class="text-[11px] text-slate-500 block">Batas maksimal kuota pembuatan tagihan QRIS yang diizinkan per bulan.</span>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: DOKU PAYMENT GATEWAY API -->
                <div v-show="activeTab === 'doku'" class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
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
                            <Link
                                :href="route('admin.monitoring.index')"
                                class="px-3.5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl transition flex items-center gap-1.5 shadow-md shadow-indigo-600/20"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                <span>Uji Coba Gate Transaksi →</span>
                            </Link>
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

                    <!-- Pengaturan Tarif Settlement DOKU -->
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Potongan Biaya Settlement DOKU</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Aktifkan potongan tarif settlement DOKU sebesar 0.7% pada penarikan dana dan rincian saldo merchant.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" v-model="form.doku_settlement_fee_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                <span class="ml-2.5 text-xs font-bold" :class="form.doku_settlement_fee_enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500'">
                                    {{ form.doku_settlement_fee_enabled ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </label>
                        </div>

                        <div v-if="form.doku_settlement_fee_enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Persentase Tarif Settlement DOKU (%)</label>
                                <div class="relative">
                                    <input
                                        v-model.number="form.doku_settlement_fee_percent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        placeholder="0.7"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-indigo-500"
                                    />
                                    <span class="absolute right-3.5 top-2.5 text-xs font-bold text-slate-400">%</span>
                                </div>
                                <span class="text-[11px] text-slate-500 mt-1 block">Default standar DOKU adalah 0.7%.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Banner Navigasi ke Monitoring Hub (Romei Protocol) -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                        <div class="space-y-0.5">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Uji Coba Gate Transaksi Produksi (Romei Protocol)</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Pengujian penerbitan invoice live DOKU dan simulasi verifikasi QRIS dipusatkan di menu Monitoring Integrasi.</p>
                        </div>
                        <Link
                            :href="route('admin.monitoring.index')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-sm shrink-0"
                        >
                            <span>Buka Monitoring Testing</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </Link>
                    </div>
                </div>

                <!-- TAB 5: MAIL GATEWAY (SMTP) -->
                <div v-show="activeTab === 'mail'" class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6 transition-colors duration-200">
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
                        class="px-6 py-2.5 bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 disabled:opacity-50 text-white dark:text-slate-950 font-bold text-xs rounded-full transition shadow-sm active:scale-95 flex items-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Semua Pengaturan' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('general');
const testEmailRecipient = ref('');
const sendingTestMail = ref(false);
const testingDoku = ref(false);

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

    // DOKU API & Settlement
    doku_client_id: props.settings?.doku_client_id || '',
    doku_secret_key: '',
    doku_base_url: props.settings?.doku_base_url || 'https://api-sandbox.doku.com',
    doku_environment: props.settings?.doku_environment || 'sandbox',
    doku_settlement_fee_enabled: props.settings?.doku_settlement_fee_enabled ?? true,
    doku_settlement_fee_percent: props.settings?.doku_settlement_fee_percent ?? 0.7,

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
</script>
