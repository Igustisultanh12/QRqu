<template>
    <button
        type="button"
        @click="toggleTheme"
        :title="isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
        class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all duration-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
        aria-label="Toggle theme"
    >
        <!-- Sun Icon for switching to light mode -->
        <svg
            v-if="isDark"
            class="w-5 h-5 text-amber-400 transition-transform duration-300 rotate-0 hover:rotate-45"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>

        <!-- Moon Icon for switching to dark mode -->
        <svg
            v-else
            class="w-5 h-5 text-indigo-600 transition-transform duration-300 -rotate-12 hover:rotate-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>
    </button>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(true);

onMounted(() => {
    const saved = localStorage.getItem('theme');
    if (saved === 'light') {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    } else if (saved === 'dark') {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        // Default to dark mode for QRqu fintech aesthetic
        isDark.value = true;
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
});

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};
</script>
