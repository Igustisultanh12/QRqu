<template>
    <AdminLayout>
        <template #header>Subscription Plan Management</template>

        <div class="space-y-6">
            <!-- Create Plan Form -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Buat Paket Berlangganan Baru</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Atur skema harga (mulai dari Rp 1), kuota transaksi, dan rate limit untuk pelanggan QRqu.</p>
                    </div>
                </div>

                <form @submit.prevent="submitPlan" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Paket</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Paket Starter Promo"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi (Hari)</label>
                        <input
                            v-model.number="form.duration_days"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Harga (IDR) <span class="text-emerald-600 dark:text-emerald-400 font-normal">(Mulai Rp 1)</span>
                        </label>
                        <input
                            v-model.number="form.price"
                            type="number"
                            required
                            min="1"
                            step="1"
                            placeholder="Mulai Rp 1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Limit Transaksi</label>
                        <input
                            v-model.number="form.transaction_limit"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">API Limit Bulanan</label>
                        <input
                            v-model.number="form.api_limit"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rate Limit (RPM)</label>
                        <input
                            v-model.number="form.rate_limit_rpm"
                            type="number"
                            required
                            min="1"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-md flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Tambah Paket' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Plans Table -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Daftar Paket Langganan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kelola dan edit harga paket berlangganan merchant</p>
                    </div>
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
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-for="plan in plans" :key="plan.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4 font-bold text-slate-900 dark:text-white">{{ plan.name }}</td>
                                <td class="p-4">{{ plan.duration_days }} hari</td>
                                <td class="p-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    Rp {{ Number(plan.price).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4">{{ plan.transaction_limit.toLocaleString('id-ID') }}</td>
                                <td class="p-4">{{ plan.api_limit.toLocaleString('id-ID') }}</td>
                                <td class="p-4 font-mono">{{ plan.rate_limit_rpm }} RPM</td>
                                <td class="p-4 font-bold text-emerald-600 dark:text-emerald-400">{{ plan.subscriptions_count }} Tenant</td>
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
                                <td class="p-4 text-center">
                                    <button
                                        @click="openEditModal(plan)"
                                        type="button"
                                        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-[11px] transition inline-flex items-center gap-1"
                                    >
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit Harga</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" @click="showEditModal = false"></div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl relative z-10 overflow-hidden transform transition-all p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Paket: {{ selectedPlan?.name }}</h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="updatePlan" class="space-y-4 pt-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Paket</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Harga (Rp) <span class="text-emerald-500 font-normal">(Mulai Rp 1)</span>
                            </label>
                            <input
                                v-model.number="editForm.price"
                                type="number"
                                required
                                min="1"
                                step="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono font-bold"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi (Hari)</label>
                            <input
                                v-model.number="editForm.duration_days"
                                type="number"
                                required
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Limit Transaksi</label>
                            <input
                                v-model.number="editForm.transaction_limit"
                                type="number"
                                required
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">API Limit Bulanan</label>
                            <input
                                v-model.number="editForm.api_limit"
                                type="number"
                                required
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rate Limit (RPM)</label>
                            <input
                                v-model.number="editForm.rate_limit_rpm"
                                type="number"
                                required
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                            <select
                                v-model="editForm.status"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-md"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    plans: Array,
});

const showEditModal = ref(false);
const selectedPlan = ref(null);

const form = useForm({
    name: '',
    duration_days: 30,
    price: 1,
    transaction_limit: 1000,
    api_limit: 10000,
    rate_limit_rpm: 60,
    status: 'active',
});

const editForm = useForm({
    id: null,
    name: '',
    duration_days: 30,
    price: 1,
    transaction_limit: 1000,
    api_limit: 10000,
    rate_limit_rpm: 60,
    status: 'active',
});

const submitPlan = () => {
    form.post(route('admin.plans.store'), {
        onSuccess: () => {
            form.reset();
            form.price = 1;
        },
    });
};

const openEditModal = (plan) => {
    selectedPlan.value = plan;
    editForm.id = plan.id;
    editForm.name = plan.name;
    editForm.duration_days = plan.duration_days;
    editForm.price = Number(plan.price);
    editForm.transaction_limit = plan.transaction_limit;
    editForm.api_limit = plan.api_limit;
    editForm.rate_limit_rpm = plan.rate_limit_rpm;
    editForm.status = plan.status;
    showEditModal.value = true;
};

const updatePlan = () => {
    if (!editForm.id) return;
    editForm.put(route('admin.plans.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};
</script>
