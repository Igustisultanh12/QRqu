<template>
    <CustomerLayout>
        <template #header>Paket Berlangganan & Kuota API</template>

        <div class="space-y-8">
            <!-- Active Subscription Status Banner -->
            <div v-if="activeSubscription" class="bg-white dark:bg-gradient-to-r dark:from-emerald-950/80 dark:to-slate-900 border border-emerald-200 dark:border-emerald-500/30 p-6 rounded-3xl shadow-sm transition-colors duration-200">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-bold mb-1">Paket Berlangganan Aktif</div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">{{ activeSubscription.plan.name }}</h2>
                        <div class="flex flex-wrap gap-4 text-xs text-slate-600 dark:text-slate-300 mt-3">
                            <div>Mulai: <strong class="text-slate-900 dark:text-white">{{ new Date(activeSubscription.starts_at).toLocaleDateString('id-ID') }}</strong></div>
                            <div>•</div>
                            <div>Berakhir: <strong class="text-slate-900 dark:text-white">{{ new Date(activeSubscription.expires_at).toLocaleDateString('id-ID') }}</strong></div>
                            <div>•</div>
                            <div>Sisa Waktu: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">{{ activeSubscription.remaining_days }} Hari</strong></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                            {{ activeSubscription.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Available Plans Selection -->
            <div>
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Pilih atau Perpanjang Paket</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pilih paket yang sesuai dengan kapasitas dan skala transaksi bisnis Anda</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="plan in plans" :key="plan.id" class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 flex flex-col justify-between hover:border-emerald-500/50 shadow-sm transition-colors duration-200">
                        <div>
                            <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ plan.duration_days }} Hari Masa Aktif</div>
                            <h4 class="text-xl font-bold text-slate-900 dark:text-white mt-1">{{ plan.name }}</h4>
                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-3">
                                Rp {{ Number(plan.price).toLocaleString('id-ID') }}
                            </div>

                            <ul class="mt-6 space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Limit Transaksi: {{ plan.transaction_limit.toLocaleString('id-ID') }}</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> API Requests: {{ plan.api_limit.toLocaleString('id-ID') }} / bln</li>
                                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Rate Limit: {{ plan.rate_limit_rpm }} RPM</li>
                                <li v-for="(feat, fIdx) in (plan.features || [])" :key="fIdx" class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> {{ feat }}
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8">
                            <button
                                @click="subscribe(plan)"
                                type="button"
                                class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-sm"
                            >
                                Aktifkan / Perpanjang
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription History Table -->
            <div class="bg-white dark:bg-slate-950 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Riwayat Berlangganan</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catatan pembayaran dan perpanjangan paket langganan Anda</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">Tanggal</th>
                                <th class="p-4">Paket</th>
                                <th class="p-4">Event</th>
                                <th class="p-4">Biaya</th>
                                <th class="p-4">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="histories.data.length === 0">
                                <td colspan="5" class="p-6 text-center text-slate-400 dark:text-slate-500">Belum ada riwayat langganan.</td>
                            </tr>
                            <tr v-for="h in histories.data" :key="h.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4 font-mono text-slate-500 dark:text-slate-400">{{ new Date(h.created_at).toLocaleString('id-ID') }}</td>
                                <td class="p-4 font-bold text-slate-900 dark:text-white">{{ h.plan?.name }}</td>
                                <td class="p-4 uppercase text-[10px] font-bold text-emerald-600 dark:text-emerald-400">{{ h.event }}</td>
                                <td class="p-4 font-mono font-semibold text-slate-800 dark:text-slate-200">Rp {{ Number(h.amount_paid).toLocaleString('id-ID') }}</td>
                                <td class="p-4 text-slate-500 dark:text-slate-400">{{ h.note || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    activeSubscription: Object,
    plans: Array,
    histories: Object,
});

const subscribe = (plan) => {
    if (confirm(`Apakah Anda yakin ingin berlangganan paket ${plan.name} seharga Rp ${Number(plan.price).toLocaleString('id-ID')}?`)) {
        router.post(route('customer.subscription.subscribe', plan.id));
    }
};
</script>
