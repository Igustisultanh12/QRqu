<template>
    <CustomerLayout>
        <template #header>Pengaturan & Log Pengiriman Webhook</template>

        <div class="space-y-6">
            <!-- Webhook Settings Card -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Konfigurasi Webhook URL</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">QRqu akan mengirim notifikasi pembayaran real-time bertanda tangan HMAC-SHA256 ke URL ini.</p>
                    </div>

                    <button
                        @click="sendTestPing"
                        :disabled="isPinging || !form.url"
                        type="button"
                        class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/40 rounded-xl text-xs font-semibold transition shrink-0 flex items-center space-x-2"
                    >
                        <svg v-if="isPinging" class="animate-spin h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ isPinging ? 'Menguji Koneksi...' : 'Kirim Test Ping Webhook' }}</span>
                    </button>
                </div>

                <form @submit.prevent="saveWebhook" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Webhook Endpoint URL (HTTPS Wajib)</label>
                        <input
                            v-model="form.url"
                            type="url"
                            required
                            placeholder="https://www.romei1.my.id/api/webhook/qrqu"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Pastikan domain dan path webhook dapat diakses publik dari server QRqu.</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Webhook Secret (Verifikasi Signature HMAC-SHA256)</label>
                            <button
                                v-if="form.secret"
                                type="button"
                                @click="copySecret"
                                class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                <span>{{ copied ? 'Tersalin!' : 'Salin Secret' }}</span>
                            </button>
                        </div>
                        <input
                            v-model="form.secret"
                            type="text"
                            placeholder="Biarkan kosong untuk generate secret baru otomatis"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-emerald-600 dark:text-emerald-400 font-mono focus:outline-none focus:border-emerald-500"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Tempelkan Webhook Secret ini pada Pengaturan Admin platform merchant (Romei) Anda agar verifikasi tanda tangan valid.</p>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs transition shadow-sm"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Webhook' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Webhook Delivery History Logs -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm transition-colors duration-200">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Log Pengiriman Webhook Terakhir</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catatan pengiriman webhook lengkap dengan diagnostik status HTTP, response body, latency, dan retry</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Event & ID</th>
                                <th class="p-4">URL Sasaran</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">HTTP</th>
                                <th class="p-4">Percobaan</th>
                                <th class="p-4">Latency</th>
                                <th class="p-4">Waktu</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="!deliveries?.data || deliveries.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Belum ada log pengiriman webhook.
                                </td>
                            </tr>
                            <template v-for="d in deliveries.data" :key="d.id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white uppercase">{{ d.event }}</div>
                                        <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500">{{ d.event_id }}</div>
                                    </td>
                                    <td class="p-4 font-mono text-[11px] text-slate-700 dark:text-slate-300 truncate max-w-[200px]" :title="d.url">{{ d.url }}</td>
                                    <td class="p-4">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                                d.status === 'DELIVERED' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                                d.status === 'RETRYING' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                                                d.status === 'PENDING' ? 'bg-sky-50 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-500/30' :
                                                'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                            ]"
                                        >
                                            {{ d.status }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-mono font-bold">
                                        <span
                                            v-if="d.http_status"
                                            :class="[
                                                'px-2 py-0.5 rounded-lg text-[10px] font-mono font-bold',
                                                d.http_status >= 200 && d.http_status < 300 ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' : 'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                            ]"
                                        >
                                            HTTP {{ d.http_status }}
                                        </span>
                                        <span
                                            v-else-if="d.attempt === 0 && d.status === 'PENDING'"
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-mono font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30"
                                        >
                                            MENUNGGU
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-mono font-bold bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30"
                                        >
                                            ERR
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500 dark:text-slate-400">{{ d.attempt }} / {{ d.max_attempts }}</td>
                                    <td class="p-4 text-slate-500 dark:text-slate-400 font-mono">{{ d.duration_ms ? d.duration_ms + 'ms' : '-' }}</td>
                                    <td class="p-4 text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ new Date(d.created_at).toLocaleString('id-ID') }}</td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="viewDetail(d)"
                                                type="button"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700 flex items-center gap-1"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Detail</span>
                                            </button>
                                            <button
                                                v-if="d.status !== 'DELIVERED'"
                                                @click="retryDelivery(d)"
                                                :disabled="retryingId === d.id"
                                                type="button"
                                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/20 dark:hover:bg-amber-500/30 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold transition border border-amber-200 dark:border-amber-500/40 disabled:opacity-50 flex items-center gap-1"
                                            >
                                                <svg v-if="retryingId === d.id" class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                <span>{{ retryingId === d.id ? 'Mengirim...' : 'Kirim Ulang' }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- INLINE DIAGNOSTIC ROW: Langsung tampilkan pesan respon / salahnya dimana -->
                                <tr v-if="d.response_body && d.status !== 'DELIVERED'" class="bg-rose-50/40 dark:bg-rose-950/20 border-b border-slate-100 dark:border-slate-800/60">
                                    <td colspan="8" class="px-4 py-2.5">
                                        <div class="flex items-start gap-2.5">
                                            <span class="px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 font-bold shrink-0 text-[10px] uppercase tracking-wider mt-0.5">
                                                Respon / Pesan Error
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-mono text-[11px] text-rose-700 dark:text-rose-300 break-all leading-relaxed bg-white/80 dark:bg-black/40 p-2 rounded-lg border border-rose-200/60 dark:border-rose-900/40">
                                                    {{ d.response_body }}
                                                </div>
                                                <!-- Saran Otomatis Jika Terkait Secret Mismatch -->
                                                <div v-if="isSignatureMismatch(d.response_body)" class="mt-1.5 p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/40 text-amber-800 dark:text-amber-200 text-xs flex items-center justify-between gap-2">
                                                    <div class="flex items-center gap-1.5">
                                                        <span>💡 <strong>Penyebab:</strong> Webhook Secret di Romei belum sama dengan Secret di QRqu.</span>
                                                    </div>
                                                    <button @click="copySecret" type="button" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded text-[11px] font-bold shrink-0">
                                                        {{ copied ? 'Tersalin!' : 'Salin Secret QRqu' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <button
                                                @click="viewDetail(d)"
                                                type="button"
                                                class="shrink-0 text-[11px] font-semibold text-rose-700 dark:text-rose-300 hover:underline flex items-center gap-0.5 pt-0.5"
                                            >
                                                <span>Buka Full Detail</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else-if="d.attempt === 0 && d.status === 'PENDING'" class="bg-amber-50/40 dark:bg-amber-950/20 border-b border-slate-100 dark:border-slate-800/60">
                                    <td colspan="8" class="px-4 py-2.5">
                                        <div class="flex items-center justify-between text-xs text-amber-800 dark:text-amber-300">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-bold shrink-0 text-[10px] uppercase">
                                                    Menunggu Queue
                                                </span>
                                                <span>Tembakan webhook ini masih dalam antrean background. Klik <strong>Kirim Ulang</strong> untuk menembak langsung secara instan.</span>
                                            </div>
                                            <button
                                                @click="retryDelivery(d)"
                                                :disabled="retryingId === d.id"
                                                class="px-3 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold transition disabled:opacity-50"
                                            >
                                                {{ retryingId === d.id ? 'Mengirim...' : 'Kirim Sekarang' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div v-if="deliveries.links && deliveries.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-1">
                    <Link
                        v-for="(link, idx) in deliveries.links"
                        :key="idx"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1 rounded-lg text-xs font-semibold transition',
                            link.active ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:text-white border border-slate-200 dark:border-slate-800',
                            !link.url ? 'opacity-40 pointer-events-none' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Detail Modal Inspector -->
        <div v-if="selectedDelivery" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Detail & Diagnostik Webhook</span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                    selectedDelivery.status === 'DELIVERED' ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' :
                                    selectedDelivery.status === 'RETRYING' ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400' :
                                    'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400'
                                ]"
                            >
                                {{ selectedDelivery.status }}
                            </span>
                        </h4>
                        <p class="font-mono text-xs text-slate-400">{{ selectedDelivery.event_id }}</p>
                    </div>
                    <button @click="selectedDelivery = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">
                        &times;
                    </button>
                </div>

                <!-- Info Cards -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Target URL & Event</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 uppercase">{{ selectedDelivery.event }}</span>
                        <p class="font-mono text-[11px] text-slate-600 dark:text-slate-400 break-all mt-0.5">{{ selectedDelivery.url }}</p>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200/60 dark:border-slate-800">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Status HTTP & Latency</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="font-bold font-mono text-xs" :class="selectedDelivery.http_status >= 200 && selectedDelivery.http_status < 300 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                {{ selectedDelivery.http_status ? 'HTTP ' + selectedDelivery.http_status : (selectedDelivery.attempt === 0 ? 'MENUNGGU QUEUE' : 'GAGAL TERHUBUNG') }}
                            </span>
                            <span class="text-slate-400">• {{ selectedDelivery.duration_ms ? selectedDelivery.duration_ms + 'ms' : '-' }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500">Percobaan ke-{{ selectedDelivery.attempt }} dari {{ selectedDelivery.max_attempts }}</span>
                    </div>
                </div>

                <!-- Signature Mismatch Suggestion Banner -->
                <div v-if="isSignatureMismatch(selectedDelivery.response_body)" class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 text-xs space-y-1.5">
                    <div class="font-bold flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Penyebab Penolakan: Signature HMAC Tidak Cocok</span>
                    </div>
                    <p class="leading-relaxed text-[11px]">
                        Server Romei menolak webhook karena Webhook Secret yang disimpan di Admin Romei berbeda dengan Webhook Secret QRqu ini.
                    </p>
                    <div class="pt-1 flex items-center gap-2">
                        <button @click="copySecret" type="button" class="px-3 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold transition">
                            {{ copied ? 'Tersalin!' : 'Salin Secret QRqu' }}
                        </button>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Buka Admin Romei &gt; Konfigurasi Payment Gateway QRqu &gt; Paste Secret &gt; Simpan.</span>
                    </div>
                </div>

                <!-- Response Body Section -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Respon dari Server Merchant (Response Body)
                        </label>
                        <button
                            v-if="selectedDelivery.response_body"
                            @click="copyText(selectedDelivery.response_body, 'response')"
                            type="button"
                            class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold hover:underline"
                        >
                            {{ copiedResponse ? 'Tersalin!' : 'Salin Respon' }}
                        </button>
                    </div>
                    <pre class="p-3 bg-slate-950 text-slate-200 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-40 border border-slate-800 whitespace-pre-wrap leading-relaxed">{{ formatJson(selectedDelivery.response_body) || '(Tidak ada response body / koneksi gagal)' }}</pre>
                </div>

                <!-- HTTP Headers Sent Section -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        HTTP Headers yang Dikirim QRqu
                    </label>
                    <div class="p-3 bg-slate-950 text-slate-300 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-32 border border-slate-800 space-y-1">
                        <div><span class="text-indigo-400">Content-Type:</span> application/json</div>
                        <div><span class="text-indigo-400">X-QRQU-Signature:</span> {{ selectedDelivery.signature }}</div>
                        <div><span class="text-indigo-400">X-QRQU-Event:</span> {{ selectedDelivery.event }}</div>
                        <div><span class="text-indigo-400">X-QRQU-Event-ID:</span> {{ selectedDelivery.event_id }}</div>
                    </div>
                </div>

                <!-- Payload Sent Section -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Payload Request (JSON yang Dikirim)</label>
                        <button
                            @click="copyText(JSON.stringify(selectedDelivery.payload, null, 2), 'payload')"
                            type="button"
                            class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold hover:underline"
                        >
                            {{ copiedPayload ? 'Tersalin!' : 'Salin JSON' }}
                        </button>
                    </div>
                    <pre class="p-3 bg-slate-950 text-emerald-400 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-40 border border-slate-800">{{ JSON.stringify(selectedDelivery.payload, null, 2) }}</pre>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button
                        v-if="selectedDelivery.status !== 'DELIVERED'"
                        @click="retryFromModal"
                        :disabled="retryingId === selectedDelivery.id"
                        type="button"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs transition disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <svg v-if="retryingId === selectedDelivery.id" class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ retryingId === selectedDelivery.id ? 'Mengirim Ulang...' : 'Kirim Ulang Sekarang' }}</span>
                    </button>
                    <div v-else></div>

                    <button
                        @click="selectedDelivery = null"
                        type="button"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    webhook: Object,
    deliveries: Object,
});

const isPinging = ref(false);
const retryingId = ref(null);
const copied = ref(false);
const copiedPayload = ref(false);
const copiedResponse = ref(false);
const selectedDelivery = ref(null);

const form = useForm({
    url: props.webhook?.url || '',
    secret: props.webhook?.secret || '',
});

const saveWebhook = () => {
    form.post(route('customer.webhooks.store'));
};

const sendTestPing = () => {
    isPinging.value = true;
    router.post(route('customer.webhooks.test-ping'), {}, {
        onFinish: () => {
            isPinging.value = false;
        }
    });
};

const retryDelivery = (delivery) => {
    retryingId.value = delivery.id;
    router.post(route('customer.webhooks.retry', delivery.id), {}, {
        onFinish: () => {
            retryingId.value = null;
        }
    });
};

const retryFromModal = () => {
    if (!selectedDelivery.value) return;
    const current = selectedDelivery.value;
    retryingId.value = current.id;
    router.post(route('customer.webhooks.retry', current.id), {}, {
        onFinish: () => {
            retryingId.value = null;
            selectedDelivery.value = null;
        }
    });
};

const viewDetail = (delivery) => {
    selectedDelivery.value = delivery;
};

const copySecret = () => {
    if (form.secret) {
        navigator.clipboard.writeText(form.secret);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};

const copyText = (text, type) => {
    if (!text) return;
    navigator.clipboard.writeText(typeof text === 'string' ? text : JSON.stringify(text, null, 2));
    if (type === 'payload') {
        copiedPayload.value = true;
        setTimeout(() => { copiedPayload.value = false; }, 2000);
    } else if (type === 'response') {
        copiedResponse.value = true;
        setTimeout(() => { copiedResponse.value = false; }, 2000);
    }
};

const isSignatureMismatch = (body) => {
    if (!body) return false;
    const str = typeof body === 'string' ? body : JSON.stringify(body);
    return str.toLowerCase().includes('signature') || str.toLowerCase().includes('tanda tangan');
};

const formatJson = (val) => {
    if (!val) return '';
    if (typeof val === 'object') return JSON.stringify(val, null, 2);
    try {
        return JSON.stringify(JSON.parse(val), null, 2);
    } catch {
        return val;
    }
};
</script>
