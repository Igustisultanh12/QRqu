<template>
    <CustomerLayout>
        <template #header>Profil Akun & Keamanan</template>

        <div class="space-y-6 max-w-4xl">
            <!-- Profile Info Form -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <h3 class="text-base font-bold text-white">Informasi Akun & Perusahaan</h3>
                <p class="text-xs text-slate-400">Perbarui identitas profil merchant dan data kontak resmi Anda.</p>

                <form @submit.prevent="saveProfile" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Perusahaan / Bisnis</label>
                        <input
                            v-model="profileForm.company_name"
                            type="text"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Email</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">No. WhatsApp / HP</label>
                        <input
                            v-model="profileForm.whatsapp"
                            type="text"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Alamat Kantor / Domisili</label>
                        <textarea
                            v-model="profileForm.address"
                            rows="2"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        ></textarea>
                    </div>

                    <div class="sm:col-span-2 pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition shadow-md"
                        >
                            {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Profil' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <h3 class="text-base font-bold text-white">Ganti Kata Sandi</h3>
                <p class="text-xs text-slate-400">Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.</p>

                <form @submit.prevent="savePassword" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Password Saat Ini</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Password Baru</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Konfirmasi Password Baru</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div class="sm:col-span-3 pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs transition border border-slate-700"
                        >
                            {{ passwordForm.processing ? 'Menyimpan...' : 'Perbarui Password' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Login Sessions -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <h3 class="text-base font-bold text-white">Riwayat Aktivitas Login Terakhir</h3>
                <div class="space-y-2.5 text-xs">
                    <div v-for="log in recent_logins" :key="log.id" class="flex justify-between py-2 border-b border-slate-800/80">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span class="text-white font-medium">{{ log.action }}</span>
                            <span class="text-slate-500 font-mono">({{ log.ip_address }})</span>
                        </div>
                        <span class="text-slate-400 font-mono text-[11px]">{{ new Date(log.created_at).toLocaleString('id-ID') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    user: Object,
    customer: Object,
    recent_logins: Array,
});

const profileForm = useForm({
    name: props.user.name,
    company_name: props.customer?.company_name || '',
    email: props.user.email,
    phone: props.customer?.phone || '',
    whatsapp: props.customer?.whatsapp || '',
    address: props.customer?.address || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const saveProfile = () => {
    profileForm.patch(route('profile.update'));
};

const savePassword = () => {
    passwordForm.put(route('password.update'), {
        onSuccess: () => passwordForm.reset(),
    });
};
</script>
