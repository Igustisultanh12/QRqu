<template>
    <CustomerLayout>
        <template #header>Penggunaan API & Structured Logs</template>

        <div class="space-y-6">
            <!-- Rate Limit & Quota Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rate Limit Aktif</span>
                    <div class="text-2xl font-black text-white mt-1">{{ rate_limit_rpm }} RPM</div>
                    <span class="text-[11px] text-slate-500 mt-2 block">Maksimal {{ rate_limit_rpm }} request per menit</span>
                </div>

                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Batas Kuota Bulanan</span>
                    <div class="text-2xl font-black text-emerald-400 mt-1">{{ monthly_limit.toLocaleString('id-ID') }}</div>
                    <span class="text-[11px] text-slate-500 mt-2 block">Direset pada awal bulan kalender</span>
                </div>

                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Protokol Keamanan</span>
                    <div class="text-2xl font-black text-teal-300 mt-1">HMAC-SHA256</div>
                    <span class="text-[11px] text-slate-500 mt-2 block">Replay Attack Protection Aktif</span>
                </div>
            </div>

            <!-- API Request Logs Table -->
            <div class="bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Log Permintaan API Real-Time</h3>
                    <p class="text-xs text-slate-400">Jejak correlation request ID untuk kemudahan audit dan troubleshooting teknis</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/60 text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="p-4">Request ID</th>
                                <th class="p-4">Method & Path</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">IP Address</th>
                                <th class="p-4">Durasi</th>
                                <th class="p-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    Belum ada log request API.
                                </td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-900/40 transition">
                                <td class="p-4 font-mono font-bold text-slate-200">{{ log.request_id }}</td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase mr-2 bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ log.method }}
                                    </span>
                                    <span class="font-mono text-slate-300">{{ log.path }}</span>
                                </td>
                                <td class="p-4 font-mono font-bold" :class="log.status_code >= 200 && log.status_code < 300 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ log.status_code }}
                                </td>
                                <td class="p-4 font-mono text-slate-400">{{ log.ip_address }}</td>
                                <td class="p-4 font-mono text-slate-400">{{ log.duration_ms }}ms</td>
                                <td class="p-4 font-mono text-[11px] text-slate-400">{{ new Date(log.created_at).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    usages: Array,
    logs: Object,
    rate_limit_rpm: Number,
    monthly_limit: Number,
});
</script>
