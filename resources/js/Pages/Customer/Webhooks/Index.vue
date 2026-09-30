<template>
    <CustomerLayout>
        <template #header>Pengaturan & Log Pengiriman Webhook</template>

        <div class="space-y-6">
            <!-- Header & Action Card -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900/60 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Konfigurasi Endpoint Webhook</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 font-bold">
                            {{ webhooks.length }} Endpoint Aktif
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Anda dapat mendaftarkan lebih dari 1 endpoint webhook untuk mengirim notifikasi pembayaran real-time (HMAC-SHA256) ke berbagai aplikasi / toko Anda.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 self-start sm:self-auto shrink-0">
                    <button
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm shadow-emerald-600/20 transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Tambah Webhook Baru</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Daftar Webhook Endpoints -->
            <div class="bg-white dark:bg-slate-900/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-3 bg-slate-50/40 dark:bg-slate-900/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold border border-indigo-200 dark:border-indigo-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Daftar Endpoint Webhook Terdaftar</h3>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500">Notifikasi callback transaksi pembayaran otomatis dikirimkan ke setiap endpoint yang aktif.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-mono uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-5">Nama & URL Endpoint</th>
                                <th class="py-3 px-4">Toko Terkait</th>
                                <th class="py-3 px-4">Webhook Secret (HMAC)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-for="wh in webhooks" :key="wh.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-900/30 transition-colors">
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ wh.name }}</span>
                                        <span v-if="wh.is_active" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-[11px] text-slate-600 dark:text-slate-400 truncate max-w-sm" :title="wh.url">
                                            {{ wh.url }}
                                        </span>
                                        <button
                                            @click="copyText(wh.url, 'url-' + wh.id)"
                                            type="button"
                                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                            :title="copiedMap['url-' + wh.id] ? 'Tersalin!' : 'Salin URL'"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                        </button>
                                        <span v-if="copiedMap['url-' + wh.id]" class="text-[9px] text-emerald-500 font-bold">Tersalin</span>
                                    </div>
                                    <div v-if="wh.description" class="text-[10px] text-slate-400 mt-0.5">
                                        {{ wh.description }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        v-if="wh.store"
                                        class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/20"
                                    >
                                        {{ wh.store.name }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                    >
                                        Semua Toko (Global)
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <code class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 bg-slate-50 dark:bg-slate-950 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-800">
                                            {{ maskSecret(wh.secret) }}
                                        </code>
                                        <button
                                            @click="copyText(wh.secret, 'secret-' + wh.id)"
                                            type="button"
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold"
                                        >
                                            {{ copiedMap['secret-' + wh.id] ? 'Tersalin!' : 'Salin' }}
                                        </button>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <button
                                        @click="toggleWebhook(wh)"
                                        type="button"
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase transition',
                                            wh.is_active
                                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700'
                                        ]"
                                    >
                                        {{ wh.is_active ? '● Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>

                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Test Ping Button -->
                                        <button
                                            @click="sendTestPing(wh.id)"
                                            :disabled="pingingId === wh.id || !wh.is_active"
                                            type="button"
                                            class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 rounded-lg text-[11px] font-bold transition disabled:opacity-40 flex items-center gap-1"
                                            title="Uji koneksi pengiriman webhook ke URL ini"
                                        >
                                            <svg v-if="pingingId === wh.id" class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span>{{ pingingId === wh.id ? 'Menguji...' : 'Test Ping' }}</span>
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            @click="openEditModal(wh)"
                                            type="button"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-[11px] font-bold transition border border-slate-200 dark:border-slate-700"
                                        >
                                            Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            @click="deleteWebhook(wh)"
                                            type="button"
                                            class="px-2 py-1 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg text-[11px] font-bold transition"
                                            title="Hapus webhook ini"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="webhooks.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Belum ada endpoint webhook yang ditambahkan. Klik tombol <strong>"+ Tambah Webhook Baru"</strong> di atas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Webhook Delivery History Logs -->
            <div class="bg-white dark:bg-slate-900/60 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm transition-colors duration-200">
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
                            <template v-for="d in deliveries?.data || []" :key="d.id">
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
                                        <span :class="d.http_status >= 200 && d.http_status < 300 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                            {{ d.http_status || '-' }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-mono text-slate-500">{{ d.attempt }} / {{ d.max_attempts }}</td>
                                    <td class="p-4 font-mono text-slate-500">{{ d.duration_ms ? d.duration_ms + 'ms' : '-' }}</td>
                                    <td class="p-4 whitespace-nowrap text-slate-500 font-mono text-[11px]">{{ d.created_at }}</td>
                                    <td class="p-4 text-right whitespace-nowrap space-x-2">
                                        <button
                                            @click="viewDetail(d)"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition"
                                        >
                                            Detail
                                        </button>
                                        <button
                                            v-if="d.status !== 'DELIVERED'"
                                            @click="retryDelivery(d)"
                                            :disabled="retryingId === d.id"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/20 dark:hover:bg-amber-500/30 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold transition disabled:opacity-50"
                                        >
                                            {{ retryingId === d.id ? 'Mengirim...' : 'Kirim Ulang' }}
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div v-if="deliveries?.links && deliveries.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-1">
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

        <!-- Modal Tambah / Edit Webhook -->
        <div v-if="isWebhookModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeWebhookModal"></div>

            <div class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl z-10 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ modalMode === 'create' ? 'Tambah Endpoint Webhook Baru' : 'Edit Endpoint Webhook' }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Kelola target URL notifikasi callback pembayaran QRIS real-time.</p>
                    </div>
                    <button @click="closeWebhookModal" class="p-1 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitWebhook" class="space-y-4">
                    <!-- Webhook Name -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Nama Endpoint / Aplikasi *</label>
                        <input
                            v-model="webhookForm.name"
                            type="text"
                            placeholder="Contoh: Webhook ROMEI / Server Discord / Bot Notifikasi"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        <span v-if="webhookForm.errors.name" class="text-[11px] text-rose-500 font-bold block">{{ webhookForm.errors.name }}</span>
                    </div>

                    <!-- Target Store -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Toko Terkait (Opsional)</label>
                        <select
                            v-model="webhookForm.store_id"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">Semua Toko (Menerima callback dari seluruh transaksi merchant)</option>
                            <option v-for="st in stores" :key="st.id" :value="st.id">
                                {{ st.name }} ({{ st.is_default ? 'Toko Utama' : st.code }})
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400">Pilih toko jika webhook ini khusus untuk sistem toko/cabang tertentu.</p>
                    </div>

                    <!-- Webhook URL -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Webhook Endpoint URL (HTTPS Wajib) *</label>
                        <input
                            v-model="webhookForm.url"
                            type="url"
                            placeholder="https://www.romei1.my.id/api/webhook/qrqu"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        <span v-if="webhookForm.errors.url" class="text-[11px] text-rose-500 font-bold block">{{ webhookForm.errors.url }}</span>
                    </div>

                    <!-- Webhook Secret -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Webhook Secret (HMAC-SHA256)</label>
                            <button
                                v-if="modalMode === 'edit' && webhookForm.secret"
                                @click="copyText(webhookForm.secret, 'modal-secret')"
                                type="button"
                                class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                            >
                                {{ copiedMap['modal-secret'] ? 'Tersalin!' : 'Salin Secret' }}
                            </button>
                        </div>
                        <input
                            v-model="webhookForm.secret"
                            type="text"
                            placeholder="Biarkan kosong untuk generate secret unik otomatis"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs font-mono text-emerald-600 dark:text-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                        <p class="text-[11px] text-slate-400">Secret ini dipakai untuk menandatangani header <code>X-QRQU-Signature</code>.</p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Keterangan Tambahan (Opsional)</label>
                        <input
                            v-model="webhookForm.description"
                            type="text"
                            placeholder="Contoh: Callback aktivasi paket otomatis"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <!-- Is Active Switch -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="is_active_toggle"
                            v-model="webhookForm.is_active"
                            type="checkbox"
                            class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500"
                        />
                        <label for="is_active_toggle" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Aktifkan Webhook Ini Segera
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="closeWebhookModal"
                            class="px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="webhookForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition disabled:opacity-50"
                        >
                            {{ webhookForm.processing ? 'Menyimpan...' : (modalMode === 'create' ? 'Tambah Webhook' : 'Simpan Perubahan') }}
                        </button>
                    </div>
                </form>
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
                            {{ copiedMap['response'] ? 'Tersalin!' : 'Salin Respon' }}
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
                            {{ copiedMap['payload'] ? 'Tersalin!' : 'Salin JSON' }}
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
import { ref, reactive } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    webhooks: {
        type: Array,
        default: () => [],
    },
    stores: {
        type: Array,
        default: () => [],
    },
    deliveries: Object,
});

const pingingId = ref(null);
const retryingId = ref(null);
const selectedDelivery = ref(null);
const copiedMap = reactive({});

const isWebhookModalOpen = ref(false);
const modalMode = ref('create'); // 'create' | 'edit'
const editingWebhookId = ref(null);

const webhookForm = useForm({
    name: '',
    url: '',
    store_id: '',
    secret: '',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    modalMode.value = 'create';
    editingWebhookId.value = null;
    webhookForm.reset();
    webhookForm.name = 'Webhook #' + (props.webhooks.length + 1);
    webhookForm.is_active = true;
    isWebhookModalOpen.value = true;
};

const openEditModal = (wh) => {
    modalMode.value = 'edit';
    editingWebhookId.value = wh.id;
    webhookForm.reset();
    webhookForm.name = wh.name;
    webhookForm.url = wh.url;
    webhookForm.store_id = wh.store_id || '';
    webhookForm.secret = wh.secret;
    webhookForm.description = wh.description || '';
    webhookForm.is_active = !!wh.is_active;
    isWebhookModalOpen.value = true;
};

const closeWebhookModal = () => {
    isWebhookModalOpen.value = false;
    webhookForm.reset();
};

const submitWebhook = () => {
    if (modalMode.value === 'create') {
        webhookForm.post(route('customer.webhooks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeWebhookModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Webhook Ditambahkan',
                    text: 'Endpoint webhook baru telah berhasil didaftarkan.',
                    confirmButtonColor: '#10b981',
                });
            },
        });
    } else {
        webhookForm.put(route('customer.webhooks.update', editingWebhookId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeWebhookModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Webhook Diperbarui',
                    text: 'Perubahan endpoint webhook berhasil disimpan.',
                    confirmButtonColor: '#10b981',
                });
            },
        });
    }
};

const toggleWebhook = (wh) => {
    router.post(route('customer.webhooks.toggle', wh.id), {}, {
        preserveScroll: true,
    });
};

const deleteWebhook = (wh) => {
    Swal.fire({
        title: 'Hapus Webhook?',
        text: `Apakah Anda yakin ingin menghapus endpoint "${wh.name}" (${wh.url})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(route('customer.webhooks.destroy', wh.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: 'Endpoint webhook telah berhasil dihapus.',
                        confirmButtonColor: '#10b981',
                    });
                },
            });
        }
    });
};

const sendTestPing = (webhookId = null) => {
    pingingId.value = webhookId;
    router.post(route('customer.webhooks.test-ping'), {
        webhook_id: webhookId,
    }, {
        preserveScroll: true,
        onFinish: () => {
            pingingId.value = null;
        }
    });
};

const retryDelivery = (delivery) => {
    retryingId.value = delivery.id;
    router.post(route('customer.webhooks.retry', delivery.id), {}, {
        preserveScroll: true,
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
        preserveScroll: true,
        onFinish: () => {
            retryingId.value = null;
            selectedDelivery.value = null;
        }
    });
};

const viewDetail = (delivery) => {
    selectedDelivery.value = delivery;
};

const maskSecret = (secret) => {
    if (!secret) return '-';
    if (secret.length <= 16) return secret;
    return secret.substring(0, 10) + '...' + secret.substring(secret.length - 6);
};

const copyText = (text, key) => {
    if (!text) return;
    navigator.clipboard.writeText(typeof text === 'string' ? text : JSON.stringify(text, null, 2));
    copiedMap[key] = true;
    setTimeout(() => {
        copiedMap[key] = false;
    }, 2000);
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
