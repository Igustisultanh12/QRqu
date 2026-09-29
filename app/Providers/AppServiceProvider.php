<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // Wajib diimport agar fungsi forceScheme aktif

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\PaymentGatewayInterface::class, 
            \App\Services\Payment\Doku\DokuService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Paksa HTTPS HANYA jika request memang menggunakan SSL (HTTPS),
        // di balik reverse proxy / Cloudflare tunnel (HTTP_X_FORWARDED_PROTO=https),
        // atau jika APP_URL memakai https dan diakses via domain (bukan IP lokal / non-standar port tanpa SSL).
        if (
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            request()->isSecure() ||
            (str_starts_with((string) config('app.url'), 'https://') && !filter_var(request()->getHost(), FILTER_VALIDATE_IP) && !in_array(request()->getHost(), ['localhost', '127.0.0.1']))
        ) {
            URL::forceScheme('https');
        }

        // DYNAMIC MAIL GATEWAY CONFIGURATION LOADER
        // Menginjeksi konfigurasi SMTP dari database SystemSetting secara real-time
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $settings = \App\Models\SystemSetting::where('group', 'mail')->orWhere('key', 'like', 'mail_%')->pluck('value', 'key')->toArray();
                if (!empty($settings['mail_host'])) {
                    config([
                        'mail.default'                 => $settings['mail_mailer'] ?? config('mail.default'),
                        'mail.mailers.smtp.host'       => $settings['mail_host'] ?? config('mail.mailers.smtp.host'),
                        'mail.mailers.smtp.port'       => (int) ($settings['mail_port'] ?? config('mail.mailers.smtp.port')),
                        'mail.mailers.smtp.encryption' => ($settings['mail_encryption'] ?? 'tls') === 'none' ? null : ($settings['mail_encryption'] ?? 'tls'),
                        'mail.mailers.smtp.username'   => $settings['mail_username'] ?? config('mail.mailers.smtp.username'),
                        'mail.mailers.smtp.password'   => $settings['mail_password'] ?? config('mail.mailers.smtp.password'),
                        'mail.from.address'            => $settings['mail_from_address'] ?? config('mail.from.address'),
                        'mail.from.name'               => $settings['mail_from_name'] ?? config('mail.from.name'),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika migrasi database belum dieksekusi
        }
    }
}