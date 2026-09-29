<template>
    <AdminLayout>
        <template #header>System Health & Infrastructure Monitoring</template>

        <div class="space-y-6 max-w-5xl">
            <!-- Health Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Database -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Database Service</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.database ? 'bg-emerald-400 animate-pulse' : 'bg-rose-500'"></span>
                    </div>
                    <div class="text-xl font-bold text-white mt-2">{{ health.database ? 'Operational' : 'Disconnected' }}</div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Koneksi SQL aktif</span>
                </div>

                <!-- Cache & Redis -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Cache & Atomic Lock</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.cache ? 'bg-emerald-400' : 'bg-rose-500'"></span>
                    </div>
                    <div class="text-xl font-bold text-white mt-2">{{ health.cache ? 'Operational' : 'Unavailable' }}</div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Redis / Memory lock ready</span>
                </div>

                <!-- DOKU Payment API -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">DOKU Payment Gateway</span>
                        <span class="w-2.5 h-2.5 rounded-full" :class="health.doku_api ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                    </div>
                    <div class="text-xl font-bold text-white mt-2">{{ health.doku_api ? 'Connected' : 'Simulation Mode' }}</div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Live API ping status</span>
                </div>

                <!-- PHP Runtime -->
                <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">PHP & Memory</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    </div>
                    <div class="text-xl font-bold text-white mt-2 font-mono">PHP {{ health.php_version }}</div>
                    <span class="text-[11px] text-slate-500 mt-1 block">Memory: {{ health.memory_usage }}</span>
                </div>
            </div>

            <!-- Queue Health Status -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <h3 class="text-base font-bold text-white">Status Antrean Background Worker (Queue Health)</h3>
                <p class="text-xs text-slate-400">Monitoring pengiriman webhook ke endpoint merchant secara asynchronous</p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div class="bg-slate-900 p-4 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 uppercase font-semibold">Pending Deliveries</span>
                        <div class="text-2xl font-black text-amber-400 mt-1">{{ health.queue_status?.pending_webhooks || 0 }}</div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Menunggu giliran worker</span>
                    </div>

                    <div class="bg-slate-900 p-4 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 uppercase font-semibold">Retrying / Backoff</span>
                        <div class="text-2xl font-black text-indigo-400 mt-1">{{ health.queue_status?.retrying_webhooks || 0 }}</div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Jadwal ulang 30s/1m/5m/15m</span>
                    </div>

                    <div class="bg-slate-900 p-4 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 uppercase font-semibold">Failed Dead-Letter</span>
                        <div class="text-2xl font-black text-rose-400 mt-1">{{ health.queue_status?.failed_webhooks || 0 }}</div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Gagal setelah 4x percobaan</span>
                    </div>
                </div>
            </div>

            <!-- Internal Health Endpoints Info -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-3">
                <h3 class="text-base font-bold text-white">Internal Health Probes (DevOps)</h3>
                <p class="text-xs text-slate-400">Endpoint untuk Kubernetes Liveness / Readiness probes atau uptime monitoring external</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono pt-2">
                    <div class="bg-slate-900 p-3 rounded-xl border border-slate-800 flex justify-between items-center">
                        <span class="text-slate-400">GET /health</span>
                        <span class="text-emerald-400 font-bold">HTTP 200 (Liveness)</span>
                    </div>
                    <div class="bg-slate-900 p-3 rounded-xl border border-slate-800 flex justify-between items-center">
                        <span class="text-slate-400">GET /ready</span>
                        <span class="text-emerald-400 font-bold">HTTP 200 (Readiness)</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    health: Object,
});
</script>
