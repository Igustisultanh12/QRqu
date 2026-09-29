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
                            <tr v-for="d in deliveries.data" :key="d.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white uppercase">{{ d.event }}</div>
                                    <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500">{{ d.event_id }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ d.customer?.company_name || d.customer?.name }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ d.customer?.email }}</div>
                                </td>
                                <td class="p-4 font-mono text-[11px] text-slate-700 dark:text-slate-300 truncate max-w-[200px]">{{ d.url }}</td>
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
                                <td class="p-4 font-mono font-bold" :class="d.http_status >= 200 && d.http_status < 300 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ d.http_status || 'ERR' }}
                                </td>
                                <td class="p-4 text-slate-500 dark:text-slate-400">{{ d.attempt }} / {{ d.max_attempts }}</td>
                                <td class="p-4 font-mono text-slate-500 dark:text-slate-400">{{ d.duration_ms ? d.duration_ms + 'ms' : '-' }}</td>
                                <td class="p-4 text-right">
                                    <button
                                        v-if="d.status !== 'DELIVERED'"
                                        @click="retryDelivery(d)"
                                        type="button"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700"
                                    >
                                        Replay
                                    </button>
                                </td>
                            </tr>
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
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    deliveries: Object,
    filters: Object,
});

const filterForm = reactive({
    status: props.filters?.status || '',
});

const applyFilters = () => {
    router.get(route('admin.webhooks.index'), filterForm, { preserveState: true });
};

const retryDelivery = (delivery) => {
    router.post(route('admin.webhooks.retry', delivery.id));
};
</script>
