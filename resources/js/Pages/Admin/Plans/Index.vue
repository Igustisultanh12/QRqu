<template>
    <AdminLayout>
        <template #header>Subscription Plan Management</template>

        <div class="space-y-6">
            <!-- Create Plan Button & Form Toggle -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Buat Paket Berlangganan Baru</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Atur skema harga, kuota transaksi, dan rate limit untuk pelanggan QRqu.</p>
                    </div>
                </div>

                <form @submit.prevent="submitPlan" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Paket</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Paket 1 Tahun"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi (Hari)</label>
                        <input
                            v-model.number="form.duration_days"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Harga (IDR)</label>
                        <input
                            v-model.number="form.price"
                            type="number"
                            required
                            min="0"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Limit Transaksi</label>
                        <input
                            v-model.number="form.transaction_limit"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">API Limit Bulanan</label>
                        <input
                            v-model.number="form.api_limit"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rate Limit (RPM)</label>
                        <input
                            v-model.number="form.rate_limit_rpm"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-md"
                        >
                            {{ form.processing ? 'Menyimpan...' : '+ Tambah Paket' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Plans Table -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Daftar Paket Langganan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Nama Paket</th>
                                <th class="p-4">Durasi</th>
                                <th class="p-4">Harga</th>
                                <th class="p-4">Limit Trx</th>
                                <th class="p-4">API Limit</th>
                                <th class="p-4">Rate Limit</th>
                                <th class="p-4">Pelanggan Aktif</th>
                                <th class="p-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-for="plan in plans" :key="plan.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4 font-bold text-slate-900 dark:text-white">{{ plan.name }}</td>
                                <td class="p-4">{{ plan.duration_days }} hari</td>
                                <td class="p-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ Number(plan.price).toLocaleString('id-ID') }}</td>
                                <td class="p-4">{{ plan.transaction_limit.toLocaleString('id-ID') }}</td>
                                <td class="p-4">{{ plan.api_limit.toLocaleString('id-ID') }}</td>
                                <td class="p-4 font-mono">{{ plan.rate_limit_rpm }} RPM</td>
                                <td class="p-4 font-bold text-indigo-600 dark:text-indigo-400">{{ plan.subscriptions_count }} Tenant</td>
                                <td class="p-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            plan.status === 'active' ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30' : 'bg-rose-50 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'
                                        ]"
                                    >
                                        {{ plan.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    plans: Array,
});

const form = useForm({
    name: '',
    duration_days: 30,
    price: 150000,
    transaction_limit: 1000,
    api_limit: 10000,
    rate_limit_rpm: 60,
    status: 'active',
});

const submitPlan = () => {
    form.post(route('admin.plans.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>
