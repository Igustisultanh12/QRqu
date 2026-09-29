<template>
    <AdminLayout>
        <template #header>Global System Settings</template>

        <div class="space-y-6 max-w-4xl">
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-white">Parameter Operasional Platform</h3>
                    <p class="text-xs text-slate-400">Atur batas waktu kedaluwarsa, toleransi waktu API, dan aturan retry webhook.</p>
                </div>

                <form @submit.prevent="submitSettings" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Aplikasi</label>
                            <input
                                v-model="form.app_name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Mata Uang</label>
                            <input
                                v-model="form.currency"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Zona Waktu (Timezone)</label>
                            <input
                                v-model="form.timezone"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Default Expiration QRIS (Menit)</label>
                            <input
                                v-model.number="form.default_expire_minutes"
                                type="number"
                                required
                                min="5"
                                max="1440"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">API Timestamp Tolerance (Detik)</label>
                            <input
                                v-model.number="form.api_timestamp_tolerance"
                                type="number"
                                required
                                min="30"
                                max="1800"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                            <span class="text-[11px] text-slate-500 mt-1 block">Toleransi selisih waktu header X-QRQU-Timestamp (default: 300s = 5 menit).</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Maksimal Retry Webhook</label>
                            <input
                                v-model.number="form.webhook_max_retries"
                                type="number"
                                required
                                min="1"
                                max="10"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                            <span class="text-[11px] text-slate-500 mt-1 block">Jumlah percobaan pengiriman ulang webhook ke server pelanggan.</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input
                                v-model="form.maintenance_mode"
                                type="checkbox"
                                class="w-4 h-4 rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500"
                            />
                            <span class="text-xs font-semibold text-slate-300">Aktifkan Maintenance Mode (Hanya Admin yang dapat login)</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-800">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition shadow-md"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Sistem' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    app_name: props.settings?.app_name || 'QRqu',
    timezone: props.settings?.timezone || 'Asia/Jakarta',
    currency: props.settings?.currency || 'IDR',
    maintenance_mode: Boolean(props.settings?.maintenance_mode),
    default_expire_minutes: props.settings?.default_expire_minutes || 60,
    api_timestamp_tolerance: props.settings?.api_timestamp_tolerance || 300,
    webhook_max_retries: props.settings?.webhook_max_retries || 4,
});

const submitSettings = () => {
    form.post(route('admin.settings.update'));
};
</script>
