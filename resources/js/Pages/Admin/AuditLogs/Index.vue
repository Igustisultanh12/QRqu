<template>
    <AdminLayout>
        <template #header>Audit Trail & Security Monitoring</template>

        <div class="space-y-6">
            <!-- Tabs for Audit vs Security -->
            <div class="flex space-x-2 border-b border-slate-800 pb-3">
                <button
                    @click="activeTab = 'audit'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition',
                        activeTab === 'audit' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'
                    ]"
                >
                    Administrative Audit Trail
                </button>
                <button
                    @click="activeTab = 'security'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition',
                        activeTab === 'security' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'
                    ]"
                >
                    Security Incident & Threat Logs
                </button>
            </div>

            <!-- Tab 1: Audit Logs -->
            <div v-if="activeTab === 'audit'" class="bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Log Aktivitas Administrator & User</h3>
                    <p class="text-xs text-slate-400">Pencatatan setiap perubahan konfigurasi, reset password, status akun, dan aksi sensitif</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/60 text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="p-4">Action</th>
                                <th class="p-4">User</th>
                                <th class="p-4">Target Type</th>
                                <th class="p-4">IP & User Agent</th>
                                <th class="p-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="auditLogs.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500">Belum ada audit log yang tercatat.</td>
                            </tr>
                            <tr v-for="log in auditLogs.data" :key="log.id" class="hover:bg-slate-900/40 transition">
                                <td class="p-4 font-bold text-white font-mono uppercase">{{ log.action }}</td>
                                <td class="p-4">
                                    <div class="font-semibold text-slate-200">{{ log.user?.name || 'System' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ log.user?.email }}</div>
                                </td>
                                <td class="p-4 font-mono text-slate-400">{{ log.target_type ? log.target_type.split('\\').pop() : '-' }} #{{ log.target_id || '' }}</td>
                                <td class="p-4 font-mono text-slate-400 text-[11px]">
                                    <div>{{ log.ip_address }}</div>
                                    <div class="truncate max-w-[200px] text-slate-600">{{ log.user_agent }}</div>
                                </td>
                                <td class="p-4 font-mono text-slate-400 text-[11px]">{{ new Date(log.created_at).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Security Incident Logs -->
            <div v-if="activeTab === 'security'" class="bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Security & API Abuse Monitoring</h3>
                    <p class="text-xs text-slate-400">Peringatan otomatis atas pelanggaran signature, replay attack, rate limit breach, dan IP tidak sah</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/60 text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="p-4">Event Type</th>
                                <th class="p-4">Severity</th>
                                <th class="p-4">Tenant / Customer</th>
                                <th class="p-4">IP Address</th>
                                <th class="p-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="securityLogs.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500">Tidak ada insiden keamanan yang terdeteksi. Sistem dalam kondisi aman.</td>
                            </tr>
                            <tr v-for="sec in securityLogs.data" :key="sec.id" class="hover:bg-slate-900/40 transition">
                                <td class="p-4 font-mono font-bold text-white uppercase">{{ sec.event_type }}</td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            sec.severity === 'high' || sec.severity === 'critical' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' :
                                            sec.severity === 'medium' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
                                            'bg-slate-800 text-slate-300'
                                        ]"
                                    >
                                        {{ sec.severity }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div v-if="sec.customer" class="font-semibold text-slate-200">{{ sec.customer.company_name || sec.customer.name }}</div>
                                    <div v-else class="text-slate-500">-</div>
                                </td>
                                <td class="p-4 font-mono text-slate-400">{{ sec.ip_address }}</td>
                                <td class="p-4 font-mono text-slate-400 text-[11px]">{{ new Date(sec.created_at).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    auditLogs: Object,
    securityLogs: Object,
});

const activeTab = ref('audit');
</script>
