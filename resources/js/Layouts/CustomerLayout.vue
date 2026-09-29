<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 flex flex-col md:flex-row antialiased transition-colors duration-200">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 bg-white dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 transition-colors duration-200">
            <div>
                <!-- Brand Logo -->
                <div class="h-16 flex items-center px-6 border-b border-slate-200 dark:border-slate-800 space-x-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <span class="font-black text-xl text-slate-950">Q</span>
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-emerald-500 to-teal-500 dark:from-emerald-400 dark:to-teal-200 bg-clip-text text-transparent">QRqu Gateway</span>
                        <span class="block text-[10px] text-emerald-600 dark:text-emerald-400/80 font-mono tracking-wider uppercase font-semibold">Merchant Portal</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="p-4 space-y-1.5 text-sm font-medium">
                    <!-- Quick Switch to Admin Portal if user is Admin -->
                    <Link
                        v-if="$page.props.auth.user?.is_admin"
                        :href="route('admin.dashboard')"
                        class="flex items-center px-3.5 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-500/30 hover:bg-indigo-100 dark:hover:bg-indigo-500/25 transition-colors text-xs mb-3 shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-2.5 shrink-0 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Masuk Panel Admin</span>
                    </Link>

                    <Link
                        :href="route('customer.dashboard')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.dashboard')
                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-500/20'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </Link>

                    <Link
                        :href="route('customer.transactions.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.transactions.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                        </svg>
                        Transaksi & Invoice
                    </Link>

                    <Link
                        :href="route('customer.reports.monthly')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.reports.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Laporan Bulanan
                    </Link>

                    <Link
                        :href="route('customer.subscription.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.subscription.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        Paket Langganan
                    </Link>

                    <Link
                        :href="$page.props.auth.user?.customer?.has_active_subscription ? route('customer.credentials.index') : route('customer.subscription.index')"
                        :class="[
                            'flex items-center justify-between px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.credentials.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            <span>API Credentials</span>
                        </div>
                        <span v-if="!$page.props.auth.user?.customer?.has_active_subscription" class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-500 dark:text-amber-400 font-bold border border-amber-500/30">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Terkunci
                        </span>
                    </Link>

                    <Link
                        :href="$page.props.auth.user?.customer?.has_active_subscription ? route('customer.webhooks.index') : route('customer.subscription.index')"
                        :class="[
                            'flex items-center justify-between px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.webhooks.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Webhooks</span>
                        </div>
                        <span v-if="!$page.props.auth.user?.customer?.has_active_subscription" class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-500 dark:text-amber-400 font-bold border border-amber-500/30">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Terkunci
                        </span>
                    </Link>

                    <Link
                        :href="$page.props.auth.user?.customer?.has_active_subscription ? route('customer.api-usage.index') : route('customer.subscription.index')"
                        :class="[
                            'flex items-center justify-between px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.api-usage.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>API Usage & Logs</span>
                        </div>
                        <span v-if="!$page.props.auth.user?.customer?.has_active_subscription" class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-500 dark:text-amber-400 font-bold border border-amber-500/30">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Terkunci
                        </span>
                    </Link>

                    <Link
                        :href="route('customer.docs.index')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('customer.docs.*')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Dokumentasi API
                    </Link>

                    <Link
                        :href="route('profile.edit')"
                        :class="[
                            'flex items-center px-3.5 py-2.5 rounded-lg transition-colors',
                            route().current('profile.edit')
                                ? 'bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                        ]"
                    >
                        <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Profil & Keamanan
                    </Link>
                </nav>
            </div>

            <!-- Customer User & Logout (Romei Style) -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 transition-colors duration-200">
                <!-- User Pill (Clickable -> Profile) -->
                <Link
                    :href="route('profile.edit')"
                    class="block bg-slate-50 hover:bg-slate-100 dark:bg-slate-900 dark:hover:bg-slate-800/80 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-400 dark:hover:border-emerald-500/50 rounded-2xl p-2.5 transition-all duration-200 group cursor-pointer shadow-sm hover:shadow"
                    title="Buka & Edit Profil Akun"
                >
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-slate-950 dark:bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm overflow-hidden ring-2 ring-transparent group-hover:ring-emerald-500/30 transition-all">
                            <img
                                v-if="$page.props.auth.user?.avatar_url"
                                :src="$page.props.auth.user.avatar_url"
                                alt="Foto Profil"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>{{ ($page.props.auth.user?.name || 'U').slice(0, 2).toUpperCase() }}</span>
                        </div>
                        <div class="truncate flex-1">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $page.props.auth.user?.name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $page.props.auth.user?.customer?.company_name || $page.props.auth.user?.email }}</p>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 shrink-0 transition-all group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <slot name="header">Portal Merchant</slot>
                    </h1>
                </div>

                <div class="flex items-center space-x-3 sm:space-x-4">
                    <div v-if="$page.props.auth.user?.customer?.has_active_subscription" class="hidden sm:flex items-center space-x-1.5 px-3 py-1 bg-emerald-500/10 border border-emerald-500/30 rounded-full text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                        <span>Subscription Aktif</span>
                    </div>
                    <div v-else class="hidden sm:flex items-center space-x-1.5 px-3 py-1 bg-amber-500/10 border border-amber-500/30 rounded-full text-xs font-semibold text-amber-600 dark:text-amber-400">
                        <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                        <span>Belum Berlangganan</span>
                    </div>

                    <Link
                        v-if="$page.props.auth.user?.is_admin"
                        :href="route('admin.dashboard')"
                        class="inline-flex items-center text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/20 hover:bg-indigo-100 dark:hover:bg-indigo-500/30 px-3 py-1.5 rounded-xl border border-indigo-200 dark:border-indigo-500/40 transition-colors shadow-sm"
                    >
                        <span>👑 Panel Admin</span>
                    </Link>

                    <a :href="route('checkout.show', 'INV-20260929-DEMO001')" target="_blank" class="hidden md:inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                        <span>Lihat Demo QRIS</span>
                        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>

                    <!-- Theme Switcher -->
                    <ThemeToggle />
                </div>
            </header>

            <!-- Unverified Email Notification Banner -->
            <div
                v-if="$page.props.auth.user && !$page.props.auth.user.has_verified_email"
                class="bg-amber-500/15 border-b border-amber-500/30 px-6 py-3.5 text-xs text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm transition-all"
            >
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <span class="font-black text-amber-800 dark:text-amber-300">Pemberitahuan Akun:</span>
                        <span class="ml-1 text-amber-700 dark:text-amber-200/90">
                            Anda belum melakukan verifikasi email (<strong>{{ $page.props.auth.user.email }}</strong>). Silakan lakukan verifikasi email Anda untuk mengamankan akun dan menikmati seluruh fitur QRqu.
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 pl-11 sm:pl-0">
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition shadow-sm inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Kirim Ulang Email</span>
                    </Link>
                    <Link
                        :href="route('verification.notice')"
                        class="px-3 py-1.5 rounded-lg bg-white/80 dark:bg-slate-800 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs border border-amber-300 dark:border-amber-500/40 transition"
                    >
                        Halaman Verifikasi
                    </Link>
                </div>
            </div>

            <!-- Flash Message Banner -->
            <div v-if="$page.props.flash.status === 'verification-link-sent'" class="m-6 mb-0 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-700 dark:text-emerald-300 text-sm flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Tautan verifikasi email baru telah berhasil dikirim ke alamat email Anda. Silakan periksa kotak masuk atau spam.</span>
                </div>
            </div>

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
