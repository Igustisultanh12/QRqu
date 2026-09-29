<template>
    <CustomerLayout>
        <template #header>Manajemen Pusat Akun</template>

        <div class="space-y-6 max-w-5xl">
            <!-- Page Heading Subtitle (Romei Style) -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen Pusat Akun</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola data informasi personal, enkripsi keamanan sandi, dan utilitas notifikasi gateway Anda.
                </p>
            </div>

            <!-- Navigation Tabs (Romei Style) -->
            <div class="flex items-center space-x-8 border-b border-slate-200 dark:border-slate-800 text-sm font-medium">
                <button
                    type="button"
                    @click="activeTab = 'profile'"
                    :class="[
                        'pb-3.5 flex items-center space-x-2 transition-all relative',
                        activeTab === 'profile'
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Profil Pengguna</span>
                    <span v-if="activeTab === 'profile'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-full"></span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'security'"
                    :class="[
                        'pb-3.5 flex items-center space-x-2 transition-all relative',
                        activeTab === 'security'
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Keamanan Sandi</span>
                    <span v-if="activeTab === 'security'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-full"></span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'notifications'"
                    :class="[
                        'pb-3.5 flex items-center space-x-2 transition-all relative',
                        activeTab === 'notifications'
                            ? 'text-indigo-600 dark:text-indigo-400 font-bold'
                            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span>Notifikasi Gateway</span>
                    <span v-if="activeTab === 'notifications'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-full"></span>
                </button>
            </div>

            <!-- TAB 1: PROFIL PENGGUNA (Exact Match to Romei Image 2) -->
            <div v-if="activeTab === 'profile'" class="space-y-6">
                <!-- Main Profile Display Card (Romei Style) -->
                <div class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-sm transition-all">
                    <!-- Top Avatar & Name Header -->
                    <div class="flex items-center space-x-5 pb-8 border-b border-slate-100 dark:border-slate-800/80">
                        <div class="w-16 h-16 rounded-full bg-slate-950 dark:bg-indigo-600 text-white flex items-center justify-center font-bold text-2xl shadow-md shrink-0">
                            {{ (user.name || 'U').charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ user.name }}</h2>
                            <div class="mt-1 flex items-center space-x-2">
                                <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded-md border border-indigo-200/60 dark:border-indigo-500/20">
                                    ID Pengguna: #{{ String(user.id).padStart(4, '0') }}
                                </span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
                                    {{ customer?.status === 'active' ? 'Aktif Terverifikasi' : 'Status: ' + (customer?.status || 'Aktif') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Details Rows (Romei Style) -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80 text-sm">
                        <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 dark:text-slate-400 font-medium sm:w-1/3">Nama Lengkap</span>
                            <span class="text-slate-900 dark:text-white font-semibold sm:w-2/3">{{ user.name }}</span>
                        </div>

                        <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 dark:text-slate-400 font-medium sm:w-1/3">Alamat Email</span>
                            <span class="text-slate-900 dark:text-white font-semibold font-mono sm:w-2/3">{{ user.email }}</span>
                        </div>

                        <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 dark:text-slate-400 font-medium sm:w-1/3">No. WhatsApp</span>
                            <span class="text-slate-900 dark:text-white font-semibold sm:w-2/3">{{ customer?.whatsapp || customer?.phone || '-' }}</span>
                        </div>

                        <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 dark:text-slate-400 font-medium sm:w-1/3">Nama Perusahaan / Bisnis</span>
                            <span class="text-slate-900 dark:text-white font-semibold sm:w-2/3">{{ customer?.company_name || '-' }}</span>
                        </div>

                        <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 dark:text-slate-400 font-medium sm:w-1/3">Alamat Kantor / Usaha</span>
                            <span class="text-slate-900 dark:text-white font-semibold sm:w-2/3">{{ customer?.address || '-' }}</span>
                        </div>

                        <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 dark:text-slate-400 font-medium sm:w-1/3">Tanggal Terdaftar</span>
                            <span class="text-slate-900 dark:text-white font-semibold sm:w-2/3">{{ formattedRegistrationDate }}</span>
                        </div>
                    </div>

                    <!-- Action Button to Toggle Edit Mode -->
                    <div class="pt-6 mt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button
                            type="button"
                            @click="isEditing = !isEditing"
                            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs transition border border-slate-200 dark:border-slate-700 flex items-center space-x-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            <span>{{ isEditing ? 'Tutup Formulir Edit' : 'Edit Informasi Profil' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Editable Form Panel (When Toggled) -->
                <div v-if="isEditing" class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-sm transition-all space-y-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Perbarui Data Profil</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Sesuaikan informasi kontak dan perusahaan resmi Anda.</p>
                    </div>

                    <form @submit.prevent="saveProfile" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap</label>
                            <input
                                v-model="profileForm.name"
                                type="text"
                                required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Perusahaan / Bisnis</label>
                            <input
                                v-model="profileForm.company_name"
                                type="text"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email Akun</label>
                            <input
                                v-model="profileForm.email"
                                type="email"
                                required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">No. WhatsApp / HP</label>
                            <input
                                v-model="profileForm.whatsapp"
                                type="text"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Kantor / Domisili Usaha</label>
                            <textarea
                                v-model="profileForm.address"
                                rows="2"
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            ></textarea>
                        </div>

                        <div class="sm:col-span-2 pt-2 flex items-center justify-end space-x-3">
                            <button
                                type="button"
                                @click="isEditing = false"
                                class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-md shadow-indigo-600/20"
                            >
                                {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: KEAMANAN SANDI (Romei Style) -->
            <div v-if="activeTab === 'security'" class="space-y-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-sm transition-all space-y-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Perbarui Kata Sandi</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pastikan akun Anda menggunakan kata sandi yang aman dan tidak digunakan di akun lain.</p>
                    </div>

                    <form @submit.prevent="savePassword" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password Saat Ini</label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password Baru</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Konfirmasi Password Baru</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div class="sm:col-span-3 pt-3 flex justify-end">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-md shadow-indigo-600/20"
                            >
                                {{ passwordForm.processing ? 'Menyimpan...' : 'Perbarui Password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 3: NOTIFIKASI GATEWAY (Romei Style) -->
            <div v-if="activeTab === 'notifications'" class="space-y-6">
                <!-- Activity Log Card -->
                <div class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-sm transition-all space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Riwayat Sesi Login & Akses Gateway</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Catatan aktivitas autentikasi terbaru yang masuk ke akun merchant Anda.</p>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                        <div v-if="recent_logins.length === 0" class="py-8 text-center text-slate-500">
                            Belum ada riwayat aktivitas yang tercatat.
                        </div>
                        <div v-for="log in recent_logins" :key="log.id" class="py-3.5 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <div>
                                    <span class="text-slate-900 dark:text-white font-semibold">{{ log.action }}</span>
                                    <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px] ml-2">IP: {{ log.ip_address }}</span>
                                </div>
                            </div>
                            <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ new Date(log.created_at).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    user: Object,
    customer: Object,
    recent_logins: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('profile');
const isEditing = ref(false);

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

const formattedRegistrationDate = computed(() => {
    if (!props.user?.created_at) return '-';
    const date = new Date(props.user.created_at);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZoneName: 'short'
    });
});

const saveProfile = () => {
    profileForm.patch(route('profile.update'), {
        onSuccess: () => {
            isEditing.value = false;
        }
    });
};

const savePassword = () => {
    passwordForm.put(route('password.update'), {
        onSuccess: () => passwordForm.reset(),
    });
};
</script>
