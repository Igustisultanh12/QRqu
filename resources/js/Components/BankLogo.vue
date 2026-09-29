<template>
    <div class="inline-flex items-center justify-center shrink-0" :class="wrapperClass">
        <!-- BCA -->
        <svg v-if="name.toLowerCase() === 'bca'" viewBox="0 0 120 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="120" height="40" rx="8" fill="#003594" />
            <text x="60" y="27" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="22" letter-spacing="2" text-anchor="middle">BCA</text>
        </svg>

        <!-- Mandiri -->
        <svg v-else-if="name.toLowerCase() === 'mandiri'" viewBox="0 0 130 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="130" height="40" rx="8" fill="#002D62" />
            <path d="M98 12C98 12 108 14 112 20C116 26 122 28 122 28C122 28 114 28 108 24C102 20 98 12 98 12Z" fill="#FDB813" />
            <text x="50" y="26" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="800" font-size="16" letter-spacing="0.5" text-anchor="middle">mandiri</text>
        </svg>

        <!-- BRI -->
        <svg v-else-if="name.toLowerCase() === 'bri'" viewBox="0 0 110 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="110" height="40" rx="8" fill="#00529C" />
            <text x="55" y="27" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="22" letter-spacing="1" text-anchor="middle">BRI</text>
            <circle cx="92" cy="16" r="3" fill="#F37021" />
        </svg>

        <!-- BNI -->
        <svg v-else-if="name.toLowerCase() === 'bni'" viewBox="0 0 110 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="110" height="40" rx="8" fill="#005E6A" />
            <text x="50" y="27" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="21" letter-spacing="1" text-anchor="middle">BNI</text>
            <path d="M85 10L98 28H90L80 14L85 10Z" fill="#F15A24" />
        </svg>

        <!-- BSI -->
        <svg v-else-if="name.toLowerCase() === 'bsi'" viewBox="0 0 110 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="110" height="40" rx="8" fill="#00A39D" />
            <circle cx="24" cy="20" r="10" fill="#EAAA00" />
            <circle cx="26" cy="18" r="8" fill="#00A39D" />
            <text x="68" y="27" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="20" letter-spacing="1" text-anchor="middle">BSI</text>
        </svg>

        <!-- GoPay -->
        <svg v-else-if="name.toLowerCase() === 'gopay'" viewBox="0 0 120 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="120" height="40" rx="8" fill="#00AED6" />
            <circle cx="25" cy="20" r="8" stroke="#FFFFFF" stroke-width="3.5" fill="none" />
            <circle cx="25" cy="20" r="3" fill="#FFFFFF" />
            <text x="70" y="26" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="800" font-size="17" letter-spacing="0.5" text-anchor="middle">gopay</text>
        </svg>

        <!-- OVO -->
        <svg v-else-if="name.toLowerCase() === 'ovo'" viewBox="0 0 110 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="110" height="40" rx="8" fill="#4C3494" />
            <text x="55" y="27" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="22" letter-spacing="2" text-anchor="middle">OVO</text>
        </svg>

        <!-- DANA -->
        <svg v-else-if="name.toLowerCase() === 'dana'" viewBox="0 0 110 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="110" height="40" rx="8" fill="#118EEA" />
            <text x="55" y="27" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="20" letter-spacing="1.5" text-anchor="middle">DANA</text>
        </svg>

        <!-- ShopeePay -->
        <svg v-else-if="name.toLowerCase() === 'shopeepay'" viewBox="0 0 140 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="140" height="40" rx="8" fill="#EE4D2D" />
            <text x="70" y="26" fill="#FFFFFF" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="800" font-size="15" letter-spacing="0.5" text-anchor="middle">ShopeePay</text>
        </svg>

        <!-- QRIS -->
        <svg v-else-if="name.toLowerCase() === 'qris'" viewBox="0 0 110 40" class="h-6 w-auto" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="110" height="40" rx="8" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="1.5" />
            <path d="M15 12H27V24H15V12Z" fill="#EE3124" />
            <path d="M21 16H23V18H21V16Z" fill="#FFFFFF" />
            <text x="64" y="27" fill="#1E293B" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-weight="900" font-size="19" letter-spacing="1.5" text-anchor="middle">QRIS</text>
        </svg>

        <!-- Fallback Text Badge -->
        <div v-else class="px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs font-mono">
            {{ name }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
    wrapperClass: {
        type: String,
        default: '',
    },
});
</script>
