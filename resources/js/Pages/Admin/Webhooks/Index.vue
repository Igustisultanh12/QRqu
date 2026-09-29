<template>
    <AdminLayout>
        <template #header>Master Webhook Deliveries</template>

        <div class="space-y-6">
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <form @submit.prevent="applyFilters" class="flex flex-wrap items-center gap-3">
                    <select
                        v-model="filterForm.status"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                    >
                        <option value="">Semua Status Delivery</option>
                        <option value="DELIVERED">DELIVERED</option>
                        <option value="RETRYING">RETRYING</option>
                        <option value="FAILED">FAILED</option>
                        <option value="PENDING">PENDING</option>
                    </select>

                    <button
                        type="submit"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-sm"
                    >
                        Filter
                    </button>
                </form>
            </div>

            <!-- Deliveries Table -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Event & ID</th>
                                <th class="p-4">Customer</th>
                                <th class="p-4">Target URL</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">HTTP</th>
                                <th class="p-4">Attempt</th>
                                <th class="p-4">Latency</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="deliveries.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">Belum ada pengiriman webhook.</td>
                            </tr>
                            <template v-for="d in deliveries.data" :key="d.id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white uppercase">{{ d.event }}</div>
                                        <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500">{{ d.event_id }}</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ d.customer?.company_name || d.customer?.name }}</div>
                                        <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ d.customer?.email }}</div>
                                    </td>
                                    <td class="p-4 font-mono text-[11px] text-slate-700 dark:text-slate-300 truncate max-w-[200px]" :title="d.url">{{ d.url }}</td>
                                    <td class="p-4">
                                        <span
                                            :class="[
                                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                                d.status === 'DELIVERED' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                                d.status === 'RETRYING' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
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
                                            v-else
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-mono font-bold bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30"
                                        >
                                            ERR
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500 dark:text-slate-400">{{ d.attempt }} / {{ d.max_attempts }}</td>
                                    <td class="p-4 font-mono text-slate-500 dark:text-slate-400">{{ d.duration_ms ? d.duration_ms + 'ms' : '-' }}</td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="selectedDelivery = d"
                                                type="button"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700"
                                            >
                                                Detail
                                            </button>
                                            <button
                                                v-if="d.status !== 'DELIVERED'"
                                                @click="retryDelivery(d)"
                                                type="button"
                                                class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/20 dark:hover:bg-amber-500/30 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-semibold transition border border-amber-200 dark:border-amber-500/40"
                                            >
                                                Replay
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- INLINE ERROR SNIPPET -->
                                <tr v-if="d.response_body && d.status !== 'DELIVERED'" class="bg-rose-50/40 dark:bg-rose-950/20 border-b border-slate-100 dark:border-slate-800/60">
                                    <td colspan="8" class="px-4 py-2">
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="px-2 py-0.5 rounded bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 font-bold shrink-0 text-[10px] uppercase">
                                                Respon Error
                                            </span>
                                            <div class="flex-1 font-mono text-[11px] text-rose-700 dark:text-rose-300 break-all leading-relaxed">
                                                {{ d.response_body }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="deliveries.links && deliveries.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-1">
                    <Link
                        v-for="(link, idx) in deliveries.links"
                        :key="idx"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1 rounded-lg text-xs font-semibold transition',
                            link.active ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:text-white border border-slate-200 dark:border-slate-800',
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
                            <span>Detail Pengiriman Webhook</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800">
                                {{ selectedDelivery.status }}
                            </span>
                        </h4>
                        <p class="font-mono text-xs text-slate-400">{{ selectedDelivery.event_id }}</p>
                    </div>
                    <button @click="selectedDelivery = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">
                        &times;
                    </button>
                </div>

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
                                {{ selectedDelivery.http_status ? 'HTTP ' + selectedDelivery.http_status : 'GAGAL TERHUBUNG' }}
                            </span>
                            <span class="text-slate-400">• {{ selectedDelivery.duration_ms ? selectedDelivery.duration_ms + 'ms' : '-' }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500">Percobaan ke-{{ selectedDelivery.attempt }} dari {{ selectedDelivery.max_attempts }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Response Body dari Server Merchant</label>
                    <pre class="p-3 bg-slate-950 text-slate-200 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-40 border border-slate-800 whitespace-pre-wrap leading-relaxed">{{ formatJson(selectedDelivery.response_body) || '(Tidak ada response body / koneksi gagal)' }}</pre>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payload Request (JSON)</label>
                    <pre class="p-3 bg-slate-950 text-emerald-400 rounded-2xl text-[11px] font-mono overflow-x-auto max-h-40 border border-slate-800">{{ JSON.stringify(selectedDelivery.payload, null, 2) }}</pre>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-200 dark:border-slate-800">
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
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    deliveries: Object,
    filters: Object,
});

const selectedDelivery = ref(null);

const filterForm = reactive({
    status: props.filters?.status || '',
});

const applyFilters = () => {
    router.get(route('admin.webhooks.index'), filterForm, { preserveState: true });
};

const retryDelivery = (delivery) => {
    router.post(route('admin.webhooks.retry', delivery.id));
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
