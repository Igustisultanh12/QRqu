<template>
    <CustomerLayout>
        <template #header>API Credentials & Kunci Rahasia</template>

        <div class="space-y-6">
            <!-- Flash Secret Reveal Modal/Card -->
            <div v-if="$page.props.flash.new_secret" class="bg-amber-50 dark:bg-amber-950/80 border-2 border-amber-500/60 p-6 rounded-3xl space-y-3 shadow-md">
                <div class="flex items-center space-x-2 text-amber-800 dark:text-amber-300 font-bold text-sm">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>SIMPAN API SECRET ANDA SEKARANG!</span>
                </div>
                <p class="text-xs text-amber-900 dark:text-amber-200/90 leading-relaxed font-medium">
                    Demi alasan keamanan enkripsi, QRqu tidak akan pernah menampilkan API Secret ini lagi setelah Anda meninggalkan halaman ini.
                </p>
                <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-amber-300 dark:border-amber-500/40 space-y-2">
                    <div>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold">API Key:</span>
                        <div class="font-mono text-xs text-slate-900 dark:text-white break-all">{{ $page.props.flash.new_key }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] text-amber-600 dark:text-amber-400 uppercase font-semibold">API Secret:</span>
                        <div class="font-mono text-xs text-emerald-600 dark:text-emerald-400 font-bold break-all">{{ $page.props.flash.new_secret }}</div>
                    </div>
                </div>
            </div>

            <!-- Create New Credential Form -->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors duration-200">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Buat API Key Baru</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
                    Pilih toko cabang yang sesuai. Setiap transaksi yang dibuat via API Key ini akan otomatis dialokasikan ke toko tersebut.
                </p>

                <form @submit.prevent="submitCreateKey" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Label / Nama Kredensial</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            required
                            placeholder="e.g. Website Utama / POS"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Toko Terkait (Cabang)</label>
                        <select
                            v-model="createForm.store_id"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option v-for="st in stores" :key="st.id" :value="st.id">
                                {{ st.name }} {{ st.is_default ? '(Toko Utama)' : '' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Environment</label>
                        <select
                            v-model="createForm.environment"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        >
                            <option value="production">Production (Live Transaction)</option>
                            <option value="sandbox">Sandbox (Testing / Simulator)</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-sm active:scale-95 flex items-center justify-center gap-1.5"
                        >
                            <span>{{ createForm.processing ? 'Membuat...' : '+ Buat API Credential' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Credentials List -->
            <div class="space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Daftar API Credentials</h3>

                <div v-if="credentials.length === 0" class="bg-white dark:bg-slate-950 p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400 shadow-sm transition-colors duration-200">
                    Belum ada kredensial API. Buat kredensial di atas untuk mengintegrasikan QRqu ke aplikasi Anda.
                </div>

                <div
                    v-for="cred in credentials"
                    :key="cred.id"
                    class="bg-white dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 space-y-4 shadow-sm transition-colors duration-200"
                >
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                        cred.environment === 'production' ? 'bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30' : 'bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30'
                                    ]"
                                >
                                    {{ cred.environment }}
                                </span>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ cred.name }}</h4>

                                <!-- Store Badge -->
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
                                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span>Toko: <strong>{{ cred.store?.name || 'Toko Utama' }}</strong></span>
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">
                                Dibuat: {{ new Date(cred.created_at).toLocaleDateString('id-ID') }} • Terakhir digunakan: {{ cred.last_used_at ? new Date(cred.last_used_at).toLocaleString('id-ID') : 'Belum pernah' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase',
                                    cred.status === 'active' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20'
                                ]"
                            >
                                {{ cred.status }}
                            </span>

                            <!-- Tombol Ubah Toko -->
                            <button
                                v-if="cred.status === 'active'"
                                @click="openEditModal(cred)"
                                type="button"
                                class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold transition border border-indigo-200 dark:border-indigo-500/20 flex items-center gap-1 active:scale-95"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Ubah Toko</span>
                            </button>

                            <button
                                v-if="cred.status === 'active'"
                                @click="revokeKey(cred)"
                                class="px-3 py-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-lg text-xs font-semibold transition border border-rose-200 dark:border-rose-500/20"
                            >
                                Revoke
                            </button>
                        </div>
                    </div>

                    <!-- API Key string and secret status -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                        <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div class="truncate mr-2">
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase block font-sans">API Key (Header: X-QRQU-Key)</span>
                                <span class="text-slate-900 dark:text-slate-200 select-all font-semibold">{{ cred.api_key }}</span>
                            </div>
                            <button @click="copy(cred.api_key)" class="text-emerald-600 dark:text-emerald-400 hover:underline font-sans text-xs font-bold shrink-0">
                                Salin
                            </button>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase block font-sans">API Secret (HMAC Signing)</span>
                                <span class="text-slate-500 dark:text-slate-400 font-sans italic">Tersimpan dalam enkripsi hash SHA-256</span>
                            </div>
                        </div>
                    </div>

                    <!-- IP Whitelist update -->
                    <div class="pt-2">
                        <details class="text-xs text-slate-600 dark:text-slate-400">
                            <summary class="cursor-pointer font-semibold hover:text-slate-900 dark:hover:text-slate-200">
                                Konfigurasi IP Whitelist (Opsional)
                            </summary>
                            <form @submit.prevent="updateIp(cred)" class="mt-3 space-y-2">
                                <textarea
                                    v-model="ipForms[cred.id]"
                                    rows="2"
                                    placeholder="Masukkan 1 IP per baris, contoh:&#10;103.12.34.56&#10;192.168.1.1"
                                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-emerald-500"
                                ></textarea>
                                <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition border border-slate-200 dark:border-slate-700">
                                    Simpan IP Whitelist
                                </button>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL UBAH TOKO & LABEL API KEY -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-indigo-500">PENGATURAN TOKO</span>
                        <h3 class="text-base font-black text-slate-900 dark:text-white mt-0.5">Ubah Toko & Label API Key</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            Pilih toko cabang tempat seluruh transaksi yang dibuat melalui API Key ini dialokasikan.
                        </p>
                    </div>
                    <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">×</button>
                </div>

                <form @submit.prevent="submitEditKey" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Label / Nama API Key</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pilih Toko Terkait (Cabang)</label>
                        <select
                            v-model="editForm.store_id"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                        >
                            <option v-for="st in stores" :key="st.id" :value="st.id">
                                {{ st.name }} {{ st.is_default ? '(Toko Utama)' : '' }}
                            </option>
                        </select>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">
                            Setiap transaksi API yang dibuat menggunakan key ini akan otomatis terhubung ke toko ini.
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20 active:scale-95 disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan Toko' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    credentials: {
        type: Array,
        default: () => [],
    },
    stores: {
        type: Array,
        default: () => [],
    },
});

const defaultStoreId = props.stores?.find(s => s.is_default)?.id || props.stores?.[0]?.id || '';

const createForm = useForm({
    name: '',
    environment: 'production',
    store_id: defaultStoreId,
});

const isEditModalOpen = ref(false);
const editingCredId = ref(null);

const editForm = useForm({
    name: '',
    store_id: '',
});

const openEditModal = (cred) => {
    editingCredId.value = cred.id;
    editForm.name = cred.name;
    editForm.store_id = cred.store_id || cred.store?.id || defaultStoreId;
    isEditModalOpen.value = true;
};

const submitEditKey = () => {
    if (!editingCredId.value) return;

    editForm.put(route('customer.credentials.update', editingCredId.value), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Toko Diperbarui',
                text: 'Toko terkait untuk API Key berhasil disimpan!',
                confirmButtonColor: '#10b981',
            });
        },
    });
};

const ipForms = reactive({});
props.credentials?.forEach(c => {
    ipForms[c.id] = c.ip_whitelist ? c.ip_whitelist.join('\n') : '';
});

const submitCreateKey = () => {
    createForm.post(route('customer.credentials.store'), {
        onSuccess: () => {
            createForm.reset();
            createForm.store_id = defaultStoreId;
        },
    });
};

const revokeKey = (cred) => {
    Swal.fire({
        title: 'Nonaktifkan API Key?',
        text: `Apakah Anda yakin ingin menonaktifkan API Key "${cred.name}"? Akses API yang menggunakan key ini akan langsung ditolak.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('customer.credentials.revoke', cred.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Dinonaktifkan',
                        text: 'API Key berhasil dinonaktifkan.',
                        confirmButtonColor: '#10b981',
                    });
                },
            });
        }
    });
};

const updateIp = (cred) => {
    router.post(route('customer.credentials.ip-whitelist', cred.id), {
        ip_whitelist: ipForms[cred.id],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'IP Whitelist berhasil diperbarui.',
                confirmButtonColor: '#10b981',
            });
        },
    });
};

const copy = (text) => {
    navigator.clipboard.writeText(text);
    Swal.fire({
        icon: 'success',
        title: 'Tersalin',
        text: 'API Key berhasil disalin ke clipboard!',
        timer: 1500,
        showConfirmButton: false,
    });
};
</script>
