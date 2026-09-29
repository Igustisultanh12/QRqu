# QRqu — QRIS Payment Gateway Berbasis DOKU

<p align="center">
  <img src="https://raw.githubusercontent.com/Igustisultanh12/QRqu/main/public/images/logo.svg" width="160" alt="QRqu Logo" onerror="this.src='https://placehold.co/160x160/2563eb/ffffff?text=QRqu'"/>
</p>

<p align="center">
  <strong>Payment Gateway QRIS Berkinerja Tinggi untuk Pelanggan & Merchant</strong><br>
  Middleware cerdas antara sistem merchant dan DOKU Payment Gateway (mengadopsi arsitektur production <em>romei 1</em>).
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.3-blue.svg" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Vue.js-3.x-green.svg" alt="Vue 3">
  <img src="https://img.shields.io/badge/Inertia.js-v2-purple.svg" alt="Inertia.js">
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-38bdf8.svg" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Tests-50%20Passed-success.svg" alt="Tests Passed">
  <img src="https://img.shields.io/badge/License-Proprietary-darkred.svg" alt="License">
</p>

---

## 📌 Daftar Isi

1. [Tentang QRqu](#-tentang-qrqu)
2. [Alur & Arsitektur Sistem](#-alur--arsitektur-sistem)
3. [Fitur Unggulan](#-fitur-unggulan)
4. [Protokol Integrasi DOKU (romei 1 Standard)](#-protokol-integrasi-doku-romei-1-standard)
5. [Spesifikasi Keamanan API](#-spesifikasi-keamanan-api)
6. [Instalasi & Konfigurasi](#-instalasi--konfigurasi)
7. [Akun Demo Pengujian](#-akun-demo-pengujian)
8. [Panduan Integrasi API Merchant](#-panduan-integrasi-api-merchant)
9. [Webhooks & Idempotensi](#-webhooks--idempotensi)
10. [Artisan CLI Commands](#-artisan-cli-commands)
11. [Pengujian Otomatis (Automated Tests)](#-pengujian-otomatis-automated-tests)

---

## 🚀 Tentang QRqu

**QRqu** adalah platform Payment Gateway QRIS mandiri berbasis Laravel 11 + Vue 3 (Inertia.js). QRqu bertindak sebagai *aggregator middleware* yang memungkinkan pelanggan mendaftar, memilih paket berlangganan, membuat API Key, lalu mengintegrasikan pembayaran QRIS DOKU langsung ke toko online, aplikasi kasir (POS), atau platform e-commerce mereka secara instan.

Setiap pembayaran QRIS diproses secara *real-time* ke **DOKU Payment Gateway**, kemudian status pelunasannya diteruskan ke merchant melalui webhook bertandatangan kriptografis (HMAC-SHA256).

---

## 🔄 Alur & Arsitektur Sistem

```text
PELANGGAN / MERCHANT
       │
       │ Registrasi & Langganan Paket
       ▼
   Portal QRqu  ───────────────► Dapatkan API Key & Secret
       │
       │ Integrasi API ke Web / Aplikasi Merchant
       ▼
Website / Toko Merchant
       │
       │ POST /api/v1/invoices (HMAC-SHA256 + Nonce + Idempotency-Key)
       ▼
  QRqu Gateway Engine
       │
       │ Redis Atomic Lock (customer_id:external_id)
       │ DOKU Signature Protocol (SHA256 Digest + HMAC-SHA256)
       ▼
  DOKU Payment Processor
       │
       │ Generate QRIS String & Checkout URL
       ▼
  QRqu Gateway Engine
       │
       │ Return Invoice & QRIS payload
       ▼
Pembeli bayar via QRIS (BCA, Mandiri, GoPay, OVO, ShopeePay, Dana, dll.)
       │
       │ Webhook Notifikasi Callback
       ▼
  DOKU Webhook Endpoint (/api/webhooks/doku)
       │
       │ DB Row Lock (lockForUpdate) & State Machine Transition (PAID)
       ▼
QRqu Queue Worker (Exponential Backoff: 30s, 60s, 300s, 900s)
       │
       │ POST Webhook Event (Signed with Secret)
       ▼
Website / Aplikasi Merchant (Update Status Transaksi Otomatis)
```

---

## ✨ Fitur Unggulan

### 1. Multi-Tenant Merchant Isolation
- Isolasi data transaksi, invoice, kredensial, dan log antar pelanggan secara ketat melalui Policy & Database Tenant Scoping.

### 2. Manajemen Paket Berlangganan (Subscription Plans)
- Starter (30 hari), Pro (90 hari), dan Business (180 hari).
- Pembatasan kuota transaksi bulanan, kuota request API harian, dan batasan *Rate Limit RPM*.
- Masa tenggang (*grace period*) dan otomatisasi *expiry check*.

### 3. Kredensial API & Sandbox Simulation
- Pemisahan kredensial **Live (Production)** dan **Sandbox (Testing)**.
- Whitelist IP opsional untuk keamanan ekstra.
- Masking API Secret dan fungsi *One-Time Reveal*.

### 4. Public QRIS Checkout Interface
- Halaman checkout publik (`/checkout/{invoice_id}`) yang responsif, modern, dan bersih.
- Menampilkan QRIS dinamis beresolusi tinggi, countdown timer kedaluwarsa invoice, dan status polling *real-time*.
- Fitur simulasi pembayaran instan untuk mode Sandbox.

### 5. Portal Pelanggan (Merchant Dashboard)
- Tinjauan metrik omset, total transaksi sukses, grafik volume transaksi harian, dan log penggunaan API.
- Manajemen invoice, riwayat transaksi, audit status webhook, serta dokumentasi API interaktif.

### 6. Control Panel Administrator
- Manajemen seluruh merchant, aktivasi paket manual, audit log sistem, monitoring performa gateway, dan pengawasan log DOKU.

### 7. Laporan Bulanan & Pengaturan Kuota Admin
- Laporan Bulanan komprehensif untuk Merchant (`/reports/monthly`) dan Admin (`/admin/reports/monthly`).
- Rekapitulasi transaksi berhasil vs gagal vs kedaluwarsa beserta **nama transaksi** (deskripsi order).
- Pemantauan sisa kuota bulanan terpakai (% kuota, limit, sisa) secara visual.
- Ekspor laporan bulanan ke format CSV dan dukungan cetak ramah printer (Print/PDF).
- Konfigurasi langsung oleh Administrator di menu Pengaturan (`/admin/settings`) untuk **Harga Paket Bulanan** dan **Batas Kuota Transaksi Bulanan**.

---

## 🛡️ Protokol Integrasi DOKU (romei 1 Standard)

QRqu mengimplementasikan 100% aturan baku integrasi DOKU yang diadopsi dari arsitektur `romei 1`:

1. **Endpoint Target**: `/checkout/v1/payment`
2. **Urutan Properti JSON**: Kunci objek `order` WAJIB tersusun alfabetis (`amount` lebih dahulu, kemudian `invoice_number`).
3. **Format Timestamp**: Format baku ISO8601 UTC tanpa milidetik (`gmdate('Y-m-d\TH:i:s') . 'Z'`).
4. **Digest Calculation**: 
   $$\text{Digest} = \text{base64\_encode}(\text{hash}(\text{'sha256'}, \text{jsonBody}, \text{true}))$$
5. **Signature String Composition**:
   ```text
   Client-Id:{CLIENT_ID}\nRequest-Id:{UUID}\nRequest-Timestamp:{ISO8601}\nRequest-Target:/checkout/v1/payment\nDigest:{DIGEST}
   ```
6. **Signature Hash**: 
   $$\text{Signature} = \text{base64\_encode}(\text{hash\_hmac}(\text{'sha256'}, \text{SignatureString}, \text{SECRET\_KEY}, \text{true}))$$
7. **Raw Body Transmission**: Menggunakan `Http::withBody($jsonBody, 'application/json')` dengan flag `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` agar Digest hash cocok 100%.

---

## 🔒 Spesifikasi Keamanan API

Setiap panggilan ke REST API QRqu (`/api/v1/*`) dilindungi oleh sistem keamanan berlapis:

| Header | Wajib | Keterangan |
|---|---|---|
| `X-QRQU-KEY` | Ya | API Key merchant (`qrqu_live_...` atau `qrqu_sand_...`) |
| `X-QRQU-TIMESTAMP` | Ya | UNIX timestamp (detik) saat request dibuat |
| `X-QRQU-NONCE` | Ya | String acak unik (UUIDv4) untuk mencegah *Replay Attack* |
| `X-QRQU-SIGNATURE` | Ya | HMAC-SHA256 dari payload |
| `Idempotency-Key` | Opsional | UUID unik untuk menjamin eksekusi tepat satu kali (*idempotent*) |

### Rumus Pembuatan Signature Merchant
$$\text{Signature} = \text{hash\_hmac}(\text{'sha256'}, \text{apiKey} + \text{timestamp} + \text{nonce} + \text{rawBody}, \text{apiSecret})$$

- **Toleransi Timestamp**: $\pm 300$ detik (5 menit).
- **Nonce Caching**: Setiap Nonce disimpan di Redis/Cache selama 10 menit; permintaan ulang dengan Nonce sama langsung ditolak dengan kode `401 REPLAY_ATTACK_DETECTED`.

---

## ⚙️ Instalasi & Konfigurasi

### Prasyarat Sistem
- PHP 8.3+ (ekstensi: `pdo`, `sqlite`/`mysql`, `curl`, `bcmath`, `mbstring`, `openssl`)
- Composer 2.x
- Node.js 18+ & NPM
- Redis Server (disarankan untuk caching & queue concurrency)

### Langkah Pemasangan

1. **Clone Repository**:
   ```bash
   git clone https://github.com/Igustisultanh12/QRqu.git
   cd QRqu
   ```

2. **Install Dependensi PHP & Node**:
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi DOKU di `.env`**:
   ```env
   DOKU_CLIENT_ID=MCH-DEMO-12345
   DOKU_SECRET_KEY=SK-DEMO-67890abcdef
   DOKU_BASE_URL=https://api-sandbox.doku.com
   DOKU_ENVIRONMENT=sandbox
   ```

5. **Migrasi Database & Seeder**:
   ```bash
   # Buat database sqlite (atau atur MySQL di .env)
   touch database/database.sqlite
   php artisan migrate --seed
   ```

6. **Build Asset Frontend**:
   ```bash
   npm run build
   ```

7. **Jalankan Development Server & Queue Worker**:
   ```bash
   # Terminal 1: Aplikasi Web
   php artisan serve

   # Terminal 2: Background Worker (untuk Webhook retries)
   php artisan queue:work
   ```

---

## 🔑 Akun Demo Pengujian

Setelah menjalankan `php artisan migrate --seed`, akun bawaan berikut siap digunakan:

### 1. Super Administrator
- **URL**: `http://localhost:8000/login`
- **Email**: `admin@qrqu.id`
- **Password**: `password123`
- **Akses**: Panel Monitoring, Manajemen Merchant, Pengaturan DOKU, Audit Logs.

### 2. Merchant / Pelanggan Demo
- **URL**: `http://localhost:8000/login`
- **Email**: `merchant@tokoku.com`
- **Password**: `password123`
- **Status Paket**: Business Plan (Aktif)
- **Live API Key**: `qrqu_live_demo1234567890abcdef12345678`
- **Sandbox API Key**: `qrqu_sand_demo1234567890abcdef12345678`
- **API Secret**: `sec_demo_secret_key_1234567890_qrqu`

---

## 💻 Panduan Integrasi API Merchant

### Contoh Pembuatan Invoice QRIS (PHP)

```php
<?php

$apiKey = 'qrqu_live_demo1234567890abcdef12345678';
$apiSecret = 'sec_demo_secret_key_1234567890_qrqu';
$timestamp = (string) time();
$nonce = bin2hex(random_bytes(16));

$data = [
    'external_id'    => 'ORDER-' . time(),
    'amount'         => 50000,
    'description'    => 'Pembayaran Pesanan #123',
    'customer_name'  => 'Ahmad Merchant',
    'customer_email' => 'ahmad@example.com',
    'customer_phone' => '081234567890',
    'webhook_url'    => 'https://tokoanda.com/api/webhook/qrqu',
    'callback_url'   => 'https://tokoanda.com/checkout/success',
    'expiry_minutes' => 60,
];

$body = json_encode($data);
$signature = hash_hmac('sha256', $apiKey . $timestamp . $nonce . $body, $apiSecret);

$ch = curl_init('https://qrqu.id/api/v1/invoices');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-QRQU-KEY: ' . $apiKey,
        'X-QRQU-TIMESTAMP: ' . $timestamp,
        'X-QRQU-NONCE: ' . $nonce,
        'X-QRQU-SIGNATURE: ' . $signature,
        'Idempotency-Key: ' . $data['external_id'],
    ],
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
// Ambil URL checkout atau string QRIS
$checkoutUrl = $result['data']['checkout_url'];
$qrString = $result['data']['qr_string'];
```

---

## 📡 Webhooks & Idempotensi

### Payload Notifikasi ke Merchant
Ketika QRIS berhasil dibayar pelanggan, QRqu mengirimkan HTTP POST ke `webhook_url` merchant:

```json
{
  "event": "payment.paid",
  "event_id": "EVT-8AB891D8F91B45C2A83",
  "invoice_id": "INV-20260929-ABC12345",
  "external_id": "ORDER-1727580000",
  "transaction_id": "TRX-20260929-XYZ98765",
  "amount": 50000,
  "status": "PAID",
  "payment_method": "QRIS",
  "paid_at": "2026-09-29T10:15:30Z",
  "timestamp": 1727580930
}
```

### Verifikasi Webhook Merchant
Header yang dikirimkan bersama webhook:
- `X-QRQU-SIGNATURE`: `hash_hmac('sha256', $rawBody, $webhookSecret)`
- `X-QRQU-EVENT`: `payment.paid`

Merchant dapat memverifikasi keaslian webhook dengan membandingkan hash HMAC-SHA256 dari raw payload menggunakan webhook secret yang terdaftar di portal QRqu.

---

## ⌨️ Artisan CLI Commands

| Command | Fungsi |
|---|---|
| `php artisan qrqu:expire-invoices` | Menandai invoice kedaluwarsa secara otomatis (dijalankan di cron per menit) |
| `php artisan qrqu:reconcile` | Rekonsiliasi transaksi gantung dengan status DOKU |
| `php artisan qrqu:api-check` | Menguji konektivitas dan kesehatan API DOKU (`romei:api-check` alias) |

---

## 🧪 Pengujian Otomatis (Automated Tests)

QRqu dilengkapi rangkaian pengujian otomatis lengkap (Unit & Feature Tests) dengan tingkat cakupan tinggi:

```bash
php artisan test
```

### Hasil Pengujian
```text
   PASS  Tests\Feature\Auth\AuthenticationTest
   PASS  Tests\Feature\Auth\EmailVerificationTest
   PASS  Tests\Feature\Auth\PasswordConfirmationTest
   PASS  Tests\Feature\Auth\PasswordResetTest
   PASS  Tests\Feature\Auth\PasswordUpdateTest
   PASS  Tests\Feature\Auth\RegistrationTest
   PASS  Tests\Feature\ProfileTest
   PASS  Tests\Feature\ApiAuthenticationTest
   PASS  Tests\Feature\InvoiceAndTransactionTest
   PASS  Tests\Feature\DokuIntegrationAndWebhookTest
   PASS  Tests\Feature\TenantIsolationTest
   PASS  Tests\Feature\SchedulerExpirationTest
   PASS  Tests\Feature\MonthlyReportAndSettingsTest

  Tests:    50 passed (193 assertions)
  Duration: 3.31s
```

---

## 📄 Lisensi & Hak Cipta

Dikembangkan oleh **Igusti Sultan** untuk infrastruktur pembayaran digital QRqu berbasis DOKU Payment Gateway. Hak Cipta dilindungi.
