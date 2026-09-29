<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 flex flex-col md:flex-row antialiased transition-colors duration-200">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 bg-white dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 transition-colors duration-200">
            <div>
                <!-- Brand Logo -->
                <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800 space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-400 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <span class="font-black text-xl text-slate-950">Q</span>
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-indigo-500 to-violet-500 dark:from-indigo-400 dark:to-violet-200 bg-clip-text text-transparent">QRqu Core</span>
                        <span class="block text-[10px] text-indigo-500 dark:text-indigo-400/80 font-mono tracking-wider uppercase font-semibold">Master Admin</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="p-4 space-y-1.5 text-sm font-medium">
                    <Link
                        :href="route('admin.dashboard')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.dashboard')
                                ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-semibold border border-indigo-200 dark:border-indigo-500/20'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Platform Dashboard
                    </Link>

                    <Link
                        :href="route('admin.customers.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.customers.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Customer Management
                    </Link>

                    <Link
                        :href="route('admin.plans.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.plans.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Subscription Plans
                    </Link>

                    <Link
                        :href="route('admin.transactions.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.transactions.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                        Master Transactions
                    </Link>

                    <Link
                        :href="route('admin.reports.monthly')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.reports.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Laporan Bulanan
                    </Link>

                    <Link
                        :href="route('admin.doku.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.doku.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        DOKU Management
                    </Link>

                    <Link
                        :href="route('admin.webhooks.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.webhooks.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Webhook Monitoring
                    </Link>

                    <Link
                        :href="route('admin.settings.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.settings.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        System Settings
                    </Link>

                    <Link
                        :href="route('admin.audit-logs.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.audit-logs.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Audit & Security Logs
                    </Link>

                    <Link
                        :href="route('admin.monitoring.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('admin.monitoring.*')
                                ? 'bg-indigo-500/10 text-indigo-400 font-semibold border border-indigo-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        System Health & Queue
                    </Link>
                </nav>
            </div>

            <!-- Admin User & Logout (Romei Style) -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 transition-colors duration-200">
                <!-- User Pill (Clickable -> Profile) -->
                <Link
                    :href="route('profile.edit')"
                    class="block bg-slate-50 hover:bg-slate-100 dark:bg-slate-900 dark:hover:bg-slate-800/80 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-500/50 rounded-2xl p-2.5 transition-all duration-200 group cursor-pointer shadow-sm hover:shadow"
                    title="Buka & Edit Profil Akun"
                >
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-slate-950 dark:bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm overflow-hidden ring-2 ring-transparent group-hover:ring-indigo-500/30 transition-all">
                            <img
                                v-if="$page.props.auth.user?.avatar_url"
                                :src="$page.props.auth.user.avatar_url"
                                alt="Foto Profil"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>{{ ($page.props.auth.user?.name || 'A').slice(0, 2).toUpperCase() }}</span>
                        </div>
                        <div class="truncate flex-1">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $page.props.auth.user?.name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $page.props.auth.user?.email }}</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 shrink-0 transition-all group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </Link>

                <!-- Keluar Aplikasi Button -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full mt-2 flex items-center space-x-2 px-3 py-2 text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 text-xs font-semibold rounded-xl hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar Aplikasi</span>
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navigation Header -->
            <header class="h-16 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center space-x-3">
                    <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                        <slot name="header">Admin Command Center</slot>
                    </h1>
                </div>

                <div class="flex items-center space-x-3 sm:space-x-4">
                    <div class="hidden sm:flex items-center space-x-1.5 px-3 py-1 bg-indigo-500/10 border border-indigo-500/30 rounded-full text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 dark:bg-indigo-400 animate-pulse"></span>
                        <span>Gateway Operational</span>
                    </div>

                    <!-- Theme Switcher -->
                    <ThemeToggle />
                </div>
            </header>

            <!-- Flash Message Banner -->
            <div v-if="$page.props.flash.success" class="m-6 mb-0 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-700 dark:text-emerald-300 text-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>

            <div v-if="$page.props.flash.error" class="m-6 mb-0 p-4 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-700 dark:text-rose-300 text-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-rose-500 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
            </div>

            <!-- Page Body Content -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
</script>