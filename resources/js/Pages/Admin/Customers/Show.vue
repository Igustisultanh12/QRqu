<template>
    <AdminLayout>
        <template #header>Detail Customer: {{ customer.name }}</template>

        <div class="space-y-6 max-w-5xl">
            <!-- Customer Summary Card & Status Actions -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2">
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                customer.status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                                customer.status === 'suspended' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
                                'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                            ]"
                        >
                            {{ customer.status }}
                        </span>
                        <h2 class="text-xl font-bold text-white">{{ customer.company_name || customer.name }}</h2>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Email: <strong class="text-slate-200">{{ customer.email }}</strong> • WA: {{ customer.whatsapp || '-' }} • ID: #{{ customer.id }}
                    </p>
                </div>

                <!-- Status Modification Buttons -->
                <div class="flex items-center space-x-2">
                    <button
                        v-if="customer.status !== 'active'"
                        @click="changeStatus('active')"
                        class="px-3 py-1.5 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 rounded-xl text-xs font-semibold transition"
                    >
                        Aktifkan Akun
                    </button>
                    <button
                        v-if="customer.status !== 'suspended'"
                        @click="changeStatus('suspended')"
                        class="px-3 py-1.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 border border-amber-500/30 rounded-xl text-xs font-semibold transition"
                    >
                        Suspend
                    </button>
                    <button
                        v-if="customer.status !== 'blocked'"
                        @click="changeStatus('blocked')"
                        class="px-3 py-1.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-400 border border-rose-500/30 rounded-xl text-xs font-semibold transition"
                    >
                        Blokir Akun
                    </button>
                </div>
            </div>

            <!-- Customer Details & Reset Password Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Info -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-3 text-xs">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Data Profil</h3>
                    <div class="flex justify-between py-2 border-b border-slate-800/80">
                        <span class="text-slate-400">Nama Pemilik</span>
                        <span class="text-white">{{ customer.name }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-800/80">
                        <span class="text-slate-400">Alamat</span>
                        <span class="text-slate-300 text-right">{{ customer.address || '-' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-800/80">
                        <span class="text-slate-400">Total API Keys</span>
                        <span class="text-emerald-400 font-bold">{{ customer.api_credentials?.length || 0 }} Key</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-slate-400">Terdaftar Sejak</span>
                        <span class="text-slate-200 font-mono">{{ new Date(customer.created_at).toLocaleString('id-ID') }}</span>
                    </div>
                </div>

                <!-- Admin Password Reset -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400">Reset Password User</h3>
                    <p class="text-xs text-slate-400">Atur password baru untuk akun merchant ini secara manual.</p>
                    <form @submit.prevent="submitResetPassword" class="space-y-3">
                        <div>
                            <input
                                v-model="resetForm.new_password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="Masukkan password baru (min 8 karakter)"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="resetForm.processing"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition border border-slate-700"
                        >
                            {{ resetForm.processing ? 'Memproses...' : 'Reset Password Sekarang' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Invoices for this customer -->
            <div class="bg-slate-950 rounded-3xl border border-slate-800 overflow-hidden">
                <div class="p-6 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white">Transaksi Terakhir Merchant Ini</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/60 text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="p-4">Transaction ID</th>
                                <th class="p-4">Invoice / External ID</th>
                                <th class="p-4">Nominal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-if="!customer.transactions || customer.transactions.length === 0">
                                <td colspan="5" class="p-6 text-center text-slate-500">Belum ada transaksi.</td>
                            </tr>
                            <tr v-for="t in customer.transactions" :key="t.id">
                                <td class="p-4 font-mono font-bold text-white">{{ t.id }}</td>
                                <td class="p-4 font-mono">{{ t.invoice_id }}</td>
                                <td class="p-4 font-bold text-emerald-400">Rp {{ Number(t.amount).toLocaleString('id-ID') }}</td>
                                <td class="p-4 uppercase font-bold text-[10px]">{{ t.status }}</td>
                                <td class="p-4 font-mono text-[11px]">{{ new Date(t.created_at).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    customer: Object,
});

const resetForm = useForm({
    new_password: '',
});

const changeStatus = (newStatus) => {
    if (confirm(`Ubah status customer menjadi ${newStatus}?`)) {
        router.post(route('admin.customers.status', props.customer.id), { status: newStatus });
    }
};

const submitResetPassword = () => {
    resetForm.post(route('admin.customers.reset-password', props.customer.id), {
        onSuccess: () => resetForm.reset(),
    });
};
</script>