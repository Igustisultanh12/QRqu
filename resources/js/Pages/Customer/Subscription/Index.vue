<template>
    <CustomerLayout>
        <template #header>Paket Berlangganan & Kuota API</template>

        <div class="space-y-8">
            <!-- 1. Pending Subscription Invoice Alert (if user has unpaid plan) -->
            <div
                v-if="pendingSubscription && pendingSubscription.invoice && pendingSubscription.invoice.status === 'PENDING'"
                class="bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-transparent border-2 border-orange-500/40 p-6 sm:p-8 rounded-[2rem] shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
            >
                <div class="flex items-start sm:items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-500 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-orange-500/30 shrink-0">
                        ⚡
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-orange-500 text-white">
                                Menunggu Pembayaran
                            </span>
                            <span class="text-xs font-mono text-slate-500 dark:text-slate-400">
                                Invoice: {{ pendingSubscription.invoice.id }}
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-950 dark:text-white">
                            Tagihan Langganan: {{ pendingSubscription.plan?.name }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Silakan lakukan pembayaran sebesar <strong class="text-slate-950 dark:text-white font-mono text-sm">Rp {{ Number(pendingSubscription.invoice.amount).toLocaleString('id-ID') }}</strong> via QRIS untuk mengaktifkan paket secara otomatis.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
                    <a
                        :href="route('checkout.show', pendingSubscription.invoice.id)"
                        class="w-full sm:w-auto px-6 py-3 rounded-full bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs tracking-tight transition shadow-lg shadow-orange-500/30 active:scale-95 flex items-center justify-center gap-2"
                    >
                        <span>Tampilkan QRIS & Bayar</span>
                        <span>➔</span>
                    </a>
                </div>
            </div>

            <!-- 2. Active Subscription Status Banner -->
            <div
                v-if="activeSubscription"
                class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-emerald-500/30 p-6 sm:p-8 rounded-[2rem] shadow-sm relative overflow-hidden"
            >
                <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-emerald-500/10 pointer-events-none blur-2xl"></div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative z-10">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono text-[11px] font-bold uppercase tracking-wider">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Paket Berlangganan Aktif</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-950 dark:text-white tracking-tight">
                            {{ activeSubscription.plan.name }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-400 font-medium pt-1">
                            <div>Mulai: <strong class="text-slate-900 dark:text-white font-mono">{{ new Date(activeSubscription.starts_at).toLocaleDateString('id-ID') }}</strong></div>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <div>Berakhir: <strong class="text-slate-900 dark:text-white font-mono">{{ new Date(activeSubscription.expires_at).toLocaleDateString('id-ID') }}</strong></div>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <div>Sisa: <strong class="text-emerald-600 dark:text-emerald-400 font-bold font-mono">{{ activeSubscription.remaining_days }} Hari</strong></div>
                        </div>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase font-mono bg-emerald-500 text-white shadow-sm">
                            {{ activeSubscription.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Available Plans Selection (Design Aligned with Welcome.vue) -->
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                    <div>
                        <span class="text-[11px] font-mono font-bold uppercase tracking-widest text-orange-500">PILIHAN PAKET</span>
                        <h3 class="text-2xl font-black text-slate-950 dark:text-white tracking-tight">
                            Investasi Tepat untuk Skala Bisnis Anda
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Pilih paket langganan dan selesaikan pembayaran QRIS untuk membuka kuota API & webhook seketika.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch pt-2">
                    <div
                        v-for="plan in formattedPlans"
                        :key="plan.id"
                        :class="[
                            'rounded-[2rem] p-8 flex flex-col justify-between transition-all duration-300 shadow-sm relative',
                            plan.is_popular
                                ? 'bg-gradient-to-b from-slate-900 to-slate-950 text-white border-2 border-orange-500 shadow-xl shadow-orange-500/15 lg:-translate-y-2 z-10'
                                : 'bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800 hover:border-slate-400 dark:hover:border-slate-700'
                        ]"
                    >
                        <!-- Popular Ribbon Badge -->
                        <div v-if="plan.is_popular" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="bg-orange-500 text-white text-[10px] font-black uppercase tracking-wider px-3.5 py-1 rounded-full shadow-md shadow-orange-500/40 font-mono">
                                PALING POPULER
                            </span>
                        </div>

                        <!-- Card Header & Pricing -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span :class="['text-xs font-bold uppercase font-mono tracking-wider', plan.is_popular ? 'text-orange-400' : 'text-slate-400']">
                                    {{ plan.duration_days }} Hari Masa Aktif
                                </span>
                                <span v-if="isActivePlan(plan)" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    Paket Anda
                                </span>
                            </div>

                            <h4 :class="['text-2xl font-black tracking-tight', plan.is_popular ? 'text-white' : 'text-slate-950 dark:text-white']">
                                {{ plan.name }}
                            </h4>

                            <div class="flex items-baseline">
                                <span :class="['text-3xl sm:text-4xl font-black font-mono', plan.is_popular ? 'text-white' : 'text-slate-950 dark:text-white']">
                                    Rp {{ Number(plan.price).toLocaleString('id-ID') }}
                                </span>
                                <span :class="['ml-2 text-xs font-medium', plan.is_popular ? 'text-slate-400' : 'text-slate-400']">
                                    / {{ plan.duration_days }} hari
                                </span>
                            </div>

                            <p :class="['text-xs leading-relaxed', plan.is_popular ? 'text-slate-300' : 'text-slate-500 dark:text-slate-400']">
                                {{ plan.description }}
                            </p>

                            <!-- Feature List -->
                            <ul :class="['space-y-2.5 text-xs pt-4 border-t', plan.is_popular ? 'border-slate-800 text-slate-200' : 'border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300']">
                                <li class="flex items-center gap-2">
                                    <span :class="plan.is_popular ? 'text-orange-400 font-bold' : 'text-emerald-500 font-bold'">✓</span>
                                    <span>Limit Transaksi: <strong>{{ plan.transaction_limit.toLocaleString('id-ID') }}</strong></span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span :class="plan.is_popular ? 'text-orange-400 font-bold' : 'text-emerald-500 font-bold'">✓</span>
                                    <span>API Requests: <strong>{{ plan.api_limit.toLocaleString('id-ID') }} / bln</strong></span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span :class="plan.is_popular ? 'text-orange-400 font-bold' : 'text-emerald-500 font-bold'">✓</span>
                                    <span>Rate Limit: <strong>{{ plan.rate_limit_rpm }} RPM</strong></span>
                                </li>
                                <li v-for="(feat, fIdx) in plan.cleanFeatures" :key="fIdx" class="flex items-center gap-2">
                                    <span :class="plan.is_popular ? 'text-orange-400 font-bold' : 'text-emerald-500 font-bold'">✓</span>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Card Action Button -->
                        <div class="pt-8 mt-auto">
                            <button
                                @click="subscribe(plan)"
                                :disabled="subscribingPlanId === plan.id"
                                type="button"
                                :class="[
                                    'w-full py-3.5 rounded-full font-bold text-xs tracking-tight transition active:scale-95 flex items-center justify-center gap-2 shadow-md',
                                    plan.is_popular
                                        ? 'bg-orange-500 hover:bg-orange-600 text-white shadow-orange-500/30'
                                        : 'bg-slate-950 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-200 text-white dark:text-slate-950'
                                ]"
                            >
                                <span v-if="subscribingPlanId === plan.id">Membuat Tagihan QRIS...</span>
                                <template v-else>
                                    <span>{{ isActivePlan(plan) ? 'Perpanjang Paket' : 'Pilih & Bayar QRIS' }}</span>
                                    <span>➔</span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Subscription History Table -->
            <div class="bg-white/90 dark:bg-slate-900/85 backdrop-blur-xl rounded-[2rem] border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="p-6 sm:p-8 border-b border-slate-100 dark:border-slate-800/80">
                    <span class="text-[11px] font-mono font-bold uppercase tracking-widest text-slate-400">RIWAYAT TRANSAKSI</span>
                    <h3 class="text-lg font-black text-slate-950 dark:text-white mt-0.5">Catatan Berlangganan</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Catatan pembayaran dan perpanjangan paket langganan merchant Anda</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-4 sm:px-6">Waktu Transaksi</th>
                                <th class="p-4">Paket</th>
                                <th class="p-4">Event</th>
                                <th class="p-4">Biaya</th>
                                <th class="p-4 sm:pr-6">Catatan Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-600 dark:text-slate-300">
                            <tr v-if="histories.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-400 dark:text-slate-500 font-medium">
                                    Belum ada riwayat langganan yang tercatat.
                                </td>
                            </tr>
                            <tr v-for="h in histories.data" :key="h.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition">
                                <td class="p-4 sm:px-6 font-mono text-slate-500 dark:text-slate-400">
                                    {{ new Date(h.created_at).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4 font-bold text-slate-950 dark:text-white">
                                    {{ h.plan?.name }}
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase font-mono bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        {{ h.event }}
                                    </span>
                                </td>
                                <td class="p-4 font-mono font-bold text-slate-900 dark:text-white">
                                    Rp {{ Number(h.amount_paid).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-4 sm:pr-6 text-slate-500 dark:text-slate-400">
                                    {{ h.note || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    activeSubscription: Object,
    pendingSubscription: Object,
    plans: Array,
    histories: Object,
});

const subscribingPlanId = ref(null);

const formattedPlans = computed(() => {
    return (props.plans || []).map((plan) => {
        // Tandai paket bisnis (misal 90 hari atau plan kedua) sebagai populer
        const isPopular = plan.duration_days === 90 || plan.slug?.includes('business') || plan.slug?.includes('quarterly');

        // Bersihkan fitur dari teks DOKU
        const cleanFeatures = (plan.features || []).map((feat) => {
            return feat.replace(/DOKU Direct Integration/gi, 'Direct Gateway Integration').replace(/DOKU/gi, 'Gateway');
        });

        let description = 'Cocok untuk proyek baru yang mulai menerima pembayaran QRIS.';
        if (plan.duration_days === 90 || isPopular) {
            description = 'Dirancang untuk bisnis berkembang dengan volume transaksi aktif harian.';
        } else if (plan.duration_days > 90) {
            description = 'Kapasitas tinggi untuk aplikasi e-commerce dan perusahaan skala besar.';
        }

        return {
            ...plan,
            is_popular: isPopular,
            cleanFeatures,
            description,
        };
    });
});

const isActivePlan = (plan) => {
    return props.activeSubscription?.plan_id === plan.id;
};

const subscribe = (plan) => {
    subscribingPlanId.value = plan.id;
    // Mengarahkan ke pembuatan tagihan invoice dan pembayaran QRIS
    router.post(route('customer.subscription.subscribe', plan.id), {}, {
        onFinish: () => {
            subscribingPlanId.value = null;
        },
    });
};
</script>
