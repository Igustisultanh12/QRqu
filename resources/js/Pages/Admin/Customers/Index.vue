<template>
    <AdminLayout>
        <template #header>Customer & Tenant Management</template>

        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center justify-between transition-colors duration-200">
                <form @submit.prevent="applyFilters" class="flex flex-wrap items-center gap-3 w-full">
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Cari nama, perusahaan, email..."
                        class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 w-full sm:w-72"
                    />

                    <select
                        v-model="filterForm.status"
                        class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="blocked">Blocked</option>
                        <option value="pending">Pending</option>
                    </select>

                    <button
                        type="submit"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-sm"
                    >
                        Filter
                    </button>
                </form>
            </div>

            <!-- Customers Table -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Merchant / Pelanggan</th>
                                <th class="p-4">Kontak</th>
                                <th class="p-4">Paket Aktif</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Terdaftar</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="customers.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada customer yang ditemukan.
                                </td>
                            </tr>
                            <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ c.name }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ c.company_name || '-' }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-slate-800 dark:text-slate-200 font-medium">{{ c.email }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ c.whatsapp || c.phone || '-' }}</div>
                                </td>
                                <td class="p-4">
                                    <span v-if="c.active_subscription" class="text-emerald-600 dark:text-emerald-400 font-semibold">
                                        {{ c.active_subscription.plan?.name }}
                                    </span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">Tidak ada paket</span>
                                </td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            c.status === 'active' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' :
                                            c.status === 'suspended' ? 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30' :
                                            'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono text-slate-500 dark:text-slate-400 text-[11px]">{{ new Date(c.created_at).toLocaleDateString('id-ID') }}</td>
                                <td class="p-4 text-right space-x-2">
                                    <Link :href="route('admin.customers.show', c.id)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700">
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="customers.links && customers.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-1">
                    <Link
                        v-for="(link, idx) in customers.links"
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
    customers: Object,
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const applyFilters = () => {
    router.get(route('admin.customers.index'), filterForm, { preserveState: true });
};
</script>