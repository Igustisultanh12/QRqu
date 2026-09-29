<template>
    <CustomerLayout>
        <template #header>Dokumentasi Integrasi API QRqu</template>

        <div class="space-y-8 max-w-5xl">
            <!-- Intro & Overview -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-3">
                <h2 class="text-xl font-bold text-white">Panduan Integrasi Pengembang</h2>
                <p class="text-xs text-slate-400 leading-relaxed">
                    QRqu menyediakan REST API dengan standar keamanan kelas perbankan menggunakan enkripsi <strong class="text-white">HMAC-SHA256 Signature</strong>, perlindungan serangan <strong class="text-white">Replay Attack</strong> melalui Nonce dan Timestamp Tolerance, serta dukungan penuh <strong class="text-white">Idempotency-Key</strong> untuk mencegah terjadinya transaksi ganda.
                </p>
                <div class="pt-2 flex items-center space-x-2 text-xs font-mono">
                    <span class="text-slate-400">Base API URL:</span>
                    <span class="bg-slate-900 px-3 py-1 rounded-lg border border-slate-800 text-emerald-400 font-bold select-all">{{ base_api_url }}</span>
                </div>
            </div>

            <!-- Authentication Protocol -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <h3 class="text-base font-bold text-white">1. Autentikasi & Header Permintaan</h3>
                <p class="text-xs text-slate-400">Setiap request ke endpoint QRqu wajib menyertakan 4 header autentikasi berikut:</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 font-semibold border-b border-slate-800">
                            <tr>
                                <th class="p-3">Nama Header</th>
                                <th class="p-3">Tipe</th>
                                <th class="p-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                            <tr>
                                <td class="p-3 text-emerald-400 font-bold">X-QRQU-Key</td>
                                <td class="p-3 text-slate-400">String</td>
                                <td class="p-3 font-sans">API Key Anda (contoh: <code class="text-slate-200">qrqu_live_...</code>)</td>
                            </tr>
                            <tr>
                                <td class="p-3 text-emerald-400 font-bold">X-QRQU-Timestamp</td>
                                <td class="p-3 text-slate-400">Integer</td>
                                <td class="p-3 font-sans">UNIX timestamp detik saat ini (Toleransi: {{ tolerance_seconds }} detik)</td>
                            </tr>
                            <tr>
                                <td class="p-3 text-emerald-400 font-bold">X-QRQU-Nonce</td>
                                <td class="p-3 text-slate-400">String</td>
                                <td class="p-3 font-sans">String unik / UUID acak per request untuk mencegah replay attack</td>
                            </tr>
                            <tr>
                                <td class="p-3 text-emerald-400 font-bold">X-QRQU-Signature</td>
                                <td class="p-3 text-slate-400">String</td>
                                <td class="p-3 font-sans">HMAC-SHA256 hex string dari gabungan payload</td>
                            </tr>
                            <tr>
                                <td class="p-3 text-teal-400 font-bold">Idempotency-Key</td>
                                <td class="p-3 text-slate-400">String (Opsional)</td>
                                <td class="p-3 font-sans">UUID unik pesanan untuk mencegah duplicate invoice jika terjadi retry/timeout</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 bg-slate-900 p-4 rounded-2xl border border-slate-800 text-xs font-mono">
                    <div class="text-[11px] text-slate-400 mb-1 font-sans font-semibold">Rumus Signature HMAC-SHA256:</div>
                    <code class="text-emerald-400 font-bold">
                        signature = hash_hmac('sha256', api_key + timestamp + nonce + raw_request_body, api_secret)
                    </code>
                </div>
            </div>

            <!-- Code Examples Tabs -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">2. Contoh Kode Pembuatan Invoice (Create Invoice)</h3>
                    <div class="flex space-x-1 bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-semibold">
                        <button
                            v-for="lang in ['PHP', 'NodeJS', 'Python', 'cURL']"
                            :key="lang"
                            @click="activeLang = lang"
                            :class="[
                                'px-3 py-1 rounded-lg transition',
                                activeLang === lang ? 'bg-emerald-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'
                            ]"
                        >
                            {{ lang }}
                        </button>
                    </div>
                </div>

                <!-- PHP Example -->
                <div v-if="activeLang === 'PHP'" class="bg-slate-900 p-4 rounded-2xl border border-slate-800 overflow-x-auto text-xs font-mono text-slate-200">
                    <pre><code>&lt;?php
$apiKey = '{{ sample_api_key }}';
$apiSecret = 'YOUR_API_SECRET';
$timestamp = time();
$nonce = bin2hex(random_bytes(16));

$body = json_encode([
    'external_id' => 'ORDER-' . time(),
    'amount' => 150000,
    'description' => 'Pembayaran Tiket Konser',
    'customer' => [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'phone' => '08123456789'
    ],
    'callback_url' => 'https://tokoku.com/checkout/return',
    'webhook_url' => 'https://tokoku.com/api/payment/webhook'
]);

$signature = hash_hmac('sha256', $apiKey . $timestamp . $nonce . $body, $apiSecret);

$ch = curl_init('{{ base_api_url }}/invoices');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-QRQU-Key: ' . $apiKey,
        'X-QRQU-Timestamp: ' . $timestamp,
        'X-QRQU-Nonce: ' . $nonce,
        'X-QRQU-Signature: ' . $signature,
        'Idempotency-Key: ' . uniqid('idemp_', true),
    ]
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
print_r($result);
</code></pre>
                </div>

                <!-- NodeJS Example -->
                <div v-if="activeLang === 'NodeJS'" class="bg-slate-900 p-4 rounded-2xl border border-slate-800 overflow-x-auto text-xs font-mono text-slate-200">
                    <pre><code>import crypto from 'crypto';
import axios from 'axios';

const apiKey = '{{ sample_api_key }}';
const apiSecret = 'YOUR_API_SECRET';
const timestamp = Math.floor(Date.now() / 1000).toString();
const nonce = crypto.randomUUID();

const payload = {
  external_id: `ORDER-${Date.now()}`,
  amount: 150000,
  description: 'Pembayaran Belanja Tokoku',
  customer: {
    name: 'Budi Santoso',
    email: 'budi@example.com'
  }
};

const rawBody = JSON.stringify(payload);
const signature = crypto
  .createHmac('sha256', apiSecret)
  .update(apiKey + timestamp + nonce + rawBody)
  .digest('hex');

const response = await axios.post('{{ base_api_url }}/invoices', payload, {
  headers: {
    'Content-Type': 'application/json',
    'X-QRQU-Key': apiKey,
    'X-QRQU-Timestamp': timestamp,
    'X-QRQU-Nonce': nonce,
    'X-QRQU-Signature': signature,
    'Idempotency-Key': crypto.randomUUID(),
  }
});

console.log(response.data);
</code></pre>
                </div>

                <!-- Python Example -->
                <div v-if="activeLang === 'Python'" class="bg-slate-900 p-4 rounded-2xl border border-slate-800 overflow-x-auto text-xs font-mono text-slate-200">
                    <pre><code>import time, uuid, hmac, hashlib, json, requests

api_key = '{{ sample_api_key }}'
api_secret = 'YOUR_API_SECRET'
timestamp = str(int(time.time()))
nonce = str(uuid.uuid4())

data = {
    "external_id": f"ORDER-{int(time.time())}",
    "amount": 150000,
    "description": "Pembayaran Tokoku"
}

raw_body = json.dumps(data)
sign_payload = (api_key + timestamp + nonce + raw_body).encode('utf-8')
signature = hmac.new(api_secret.encode('utf-8'), sign_payload, hashlib.sha256).hexdigest()

headers = {
    "Content-Type": "application/json",
    "X-QRQU-Key": api_key,
    "X-QRQU-Timestamp": timestamp,
    "X-QRQU-Nonce": nonce,
    "X-QRQU-Signature": signature,
    "Idempotency-Key": str(uuid.uuid4()),
}

res = requests.post("{{ base_api_url }}/invoices", data=raw_body, headers=headers)
print(res.json())
</code></pre>
                </div>

                <!-- cURL Example -->
                <div v-if="activeLang === 'cURL'" class="bg-slate-900 p-4 rounded-2xl border border-slate-800 overflow-x-auto text-xs font-mono text-slate-200">
                    <pre><code>curl -X POST "{{ base_api_url }}/invoices" \
  -H "Content-Type: application/json" \
  -H "X-QRQU-Key: {{ sample_api_key }}" \
  -H "X-QRQU-Timestamp: 1727600000" \
  -H "X-QRQU-Nonce: 4f8b9123-xxxx-xxxx" \
  -H "X-QRQU-Signature: CALCULATED_HMAC_SHA256_HEX" \
  -H "Idempotency-Key: e9a8b1c2-xxxx" \
  -d '{
    "external_id": "ORDER-001",
    "amount": 150000,
    "description": "Pembayaran Order #001"
  }'
</code></pre>
                </div>
            </div>

            <!-- Webhook Verification Guide -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 space-y-3">
                <h3 class="text-base font-bold text-white">3. Menangani Webhook Notifikasi Pembayaran</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Ketika pelanggan melunasi tagihan QRIS, QRqu akan mengirim HTTP POST ke URL Webhook Anda. Header permintaan menyertakan <code class="text-emerald-400">X-QRQU-Signature</code> yang dihitung dari:
                </p>
                <div class="bg-slate-900 p-3.5 rounded-xl border border-slate-800 font-mono text-xs text-emerald-400">
                    hash_hmac('sha256', raw_payload_json, webhook_secret)
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Pastikan endpoint webhook Anda mengembalikan HTTP response <code class="text-white font-bold">200 OK</code> untuk menandakan penerimaan sukses. Jika gagal atau timeout, QRqu akan melakukan retry otomatis secara bertahap (30s, 1m, 5m, 15m).
                </p>
            </div>
        </div>
    </CustomerLayout>
</template>

<script setup>
import { ref } from 'vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

defineProps({
    sample_api_key: String,
    base_api_url: String,
    tolerance_seconds: Number,
});

const activeLang = ref('PHP');
</script>
