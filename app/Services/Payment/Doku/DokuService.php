<?php

namespace App\Services\Payment\Doku;

use App\Contracts\PaymentGatewayInterface;
use App\Models\DokuTransaction;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuService implements PaymentGatewayInterface
{
    /**
     * AMBIL KONFIGURASI DARI PUSAT KOMANDO (PLAIN TEXT)
     * Mengikuti struktur romei 1 dengan fallback ke .env
     */
    public function getConfig(): array
    {
        $settings = SystemSetting::pluck('value', 'key')->all();

        return [
            'client_id'  => !empty($settings['doku_client_id']) ? trim($settings['doku_client_id']) : env('DOKU_CLIENT_ID', ''),
            'secret_key' => !empty($settings['doku_secret_key']) ? trim($settings['doku_secret_key']) : env('DOKU_SECRET_KEY', ''),
            'base_url'   => rtrim($settings['doku_api_base_url'] ?? $settings['doku_base_url'] ?? env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'), '/'),
        ];
    }

    /**
     * GENERATE QRIS
     * Mengikuti protokol romei 1: Direct Checkout Page Live / QRIS
     */
    public function generateQris($transaction, $invoice = null): ?array
    {
        $config = $this->getConfig();
        $targetPath = '/checkout/v1/payment';

        if (!$invoice) {
            $invoice = $transaction->invoice;
        }

        $customerName = $invoice->customer_name ?? $transaction->customer?->name ?? 'Pelanggan QRqu';
        $customerEmail = $invoice->customer_email ?? $transaction->customer?->email ?? 'billing@qrqu.id';
        $callbackUrl = $invoice->callback_url ?: url('/checkout/' . $invoice->id . '?status=return');

        // KUNCI UTAMA 1: Urutan properti objek 'order' WAJIB diatur alfabetis (amount dulu, baru invoice_number)
        // Hal ini krusial karena DOKU Live memeriksa kecocokan string Digest berdasarkan urutan pengiriman ini.
        $body = [
            'order' => [
                'amount' => (int) $transaction->amount,
                'invoice_number' => $invoice->id,
                'callback_url' => $callbackUrl,
            ],
            'payment' => [
                'payment_due_date' => 60, // 60 menit
                'payment_method_types' => ['QRIS'],
            ],
            'customer' => [
                'id' => 'CUST-' . ($transaction->customer_id ?? 'GUEST'),
                'name' => $customerName,
                'email' => $customerEmail,
            ],
        ];

        // Jika Client ID atau Secret Key kosong, jalankan mode simulasi sandbox yang aman
        if (empty($config['client_id']) || empty($config['secret_key'])) {
            Log::info("DOKU Sandbox Simulator: Mocking QRIS for Invoice {$invoice->id}");
            $dummyQrUrl = url("/checkout/{$invoice->id}");
            $dummyQrString = "00020101021226670016ID.CO.QRQU.WWW01189360000000000000010215{$invoice->id}520458125303360540" .
                strlen((string)(int)$transaction->amount) . ((int)$transaction->amount) . "5802ID5912QRQU GATEWAY6007JAKARTA6304ABCD";

            DokuTransaction::create([
                'transaction_id' => $transaction->id,
                'invoice_number' => $invoice->id,
                'request_id' => (string) Str::uuid(),
                'client_id' => 'SIMULATOR',
                'amount' => $transaction->amount,
                'doku_url' => $dummyQrUrl,
                'request_payload' => $body,
                'response_payload' => ['mock' => true, 'payment' => ['url' => $dummyQrUrl, 'qr_string' => $dummyQrString]],
                'status' => 'PENDING',
            ]);

            return [
                'payment_url' => $dummyQrUrl,
                'qr_string' => $dummyQrString,
                'request_id' => (string) Str::uuid(),
                'raw_response' => ['mock' => true],
            ];
        }

        $requestId = (string) Str::uuid();
        $response = $this->executeRequest($targetPath, $body, $config, $requestId);

        $dokuUrl = $response['response']['payment']['url'] ?? $response['payment']['url'] ?? null;
        $qrString = $response['response']['payment']['qr_string'] ?? $response['payment']['qr_string'] ?? null;

        // Catat ke doku_transactions
        DokuTransaction::create([
            'transaction_id' => $transaction->id,
            'invoice_number' => $invoice->id,
            'request_id' => $requestId,
            'client_id' => $config['client_id'],
            'amount' => $transaction->amount,
            'doku_url' => $dokuUrl,
            'request_payload' => $body,
            'response_payload' => $response,
            'status' => $dokuUrl ? 'PENDING' : 'FAILED',
        ]);

        if ($dokuUrl || $qrString) {
            return [
                'payment_url' => $dokuUrl,
                'qr_string' => $qrString,
                'request_id' => $requestId,
                'raw_response' => $response,
            ];
        }

        Log::error('QRqu - DOKU API Refused Payload: ' . json_encode($response));
        return null;
    }

    /**
     * EKSEKUSI REQUEST (Protokol Sinkronisasi Signature Mas Sultan / romei 1)
     */
    private function executeRequest(string $targetPath, array $body, array $config, ?string $requestId = null): ?array
    {
        $requestId = $requestId ?: (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s') . 'Z'; // Aturan baku ISO8601 UTC tanpa milidetik

        // KUNCI UTAMA 3: Gunakan flags JSON_UNESCAPED_SLASHES agar json_encode PHP tidak merusak parameter url
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Kalkulasi nilai Digest SHA-256 riil
        $digest = base64_encode(hash('sha256', $jsonBody, true));

        // Komponen penyusun wajib lurus rapi tanpa jeda spasi tersembunyi
        $signatureString = "Client-Id:" . $config['client_id'] . "\n" .
            "Request-Id:" . $requestId . "\n" .
            "Request-Timestamp:" . $timestamp . "\n" .
            "Request-Target:" . $targetPath . "\n" .
            "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $signatureString, $config['secret_key'], true));

        try {
            // KUNCI UTAMA 4: Gunakan ->withBody() mengirimkan raw string JSON murni agar identik 100% dengan hash Digest
            $response = Http::withHeaders([
                'Client-Id'         => $config['client_id'],
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => "HMACSHA256=" . $signature,
                'Content-Type'      => 'application/json',
            ])
                ->timeout(15)
                ->withBody($jsonBody, 'application/json')
                ->post($config['base_url'] . $targetPath);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('QRqu - DOKU Connection Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cek status koneksi atau ping DOKU (untuk artisan command romei:api-check / qrqu:check)
     */
    public function verifyPayment(string $reference): array
    {
        $config = $this->getConfig();
        if (empty($config['client_id']) || empty($config['secret_key'])) {
            return [
                'status' => 'CONNECTED_SIMULATION',
                'message' => 'Running in sandbox simulation mode',
            ];
        }

        $targetPath = '/orders/v1/status/' . $reference;
        $requestId = (string) Str::uuid();
        $timestamp = gmdate('Y-m-d\TH:i:s') . 'Z';

        $signatureString = "Client-Id:" . $config['client_id'] . "\n" .
            "Request-Id:" . $requestId . "\n" .
            "Request-Timestamp:" . $timestamp . "\n" .
            "Request-Target:" . $targetPath;

        $signature = base64_encode(hash_hmac('sha256', $signatureString, $config['secret_key'], true));

        try {
            $response = Http::withHeaders([
                'Client-Id'         => $config['client_id'],
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => "HMACSHA256=" . $signature,
            ])->timeout(10)->get($config['base_url'] . $targetPath);

            return $response->json() ?? ['status' => 'OK'];
        } catch (\Exception $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }

    /**
     * Verifikasi status transaksi ke DOKU dan sinkronkan jika sudah dibayar
     */
    public function syncTransactionWithDoku(Transaction $transaction, ?\App\Services\Webhook\CustomerWebhookService $webhookService = null): array
    {
        if (in_array(strtoupper($transaction->status), ['PAID', 'SUCCESS'], true)) {
            return [
                'success' => true,
                'is_paid' => true,
                'status' => 'PAID',
                'doku_reference' => $transaction->doku_reference,
                'message' => 'Transaksi sudah berstatus lunas sebelumnya.',
            ];
        }

        $invoice = $transaction->invoice;
        if (!$invoice) {
            return [
                'success' => false,
                'is_paid' => false,
                'status' => $transaction->status,
                'message' => 'Invoice transaksi tidak ditemukan.',
            ];
        }

        try {
            $dokuStatus = $this->verifyPayment($invoice->id);
            $statusUpper = strtoupper($dokuStatus['transaction']['status'] ?? $dokuStatus['status'] ?? '');

            if (in_array($statusUpper, ['SUCCESS', 'PAID'], true)) {
                $dokuReference = $dokuStatus['transaction']['original_request_id']
                    ?? $dokuStatus['emoney_payment']['approval_code']
                    ?? $dokuStatus['emoney_payment']['reference_number']
                    ?? $dokuStatus['transaction']['id']
                    ?? ('DOKU-' . time());

                \Illuminate\Support\Facades\DB::transaction(function () use ($transaction, $invoice, $dokuReference) {
                    $invoice->update([
                        'status' => 'PAID',
                        'paid_at' => now(),
                    ]);

                    $transaction->update([
                        'status' => 'PAID',
                        'doku_reference' => $dokuReference,
                        'payment_gateway_ref' => $dokuReference,
                    ]);

                    $transaction->transitionTo('PAID', 'doku_verify', "DOKU payment verified (Ref: {$dokuReference})", [
                        'doku_reference' => $dokuReference,
                    ]);
                });

                \Illuminate\Support\Facades\Cache::put('payment_status_' . $invoice->id, 'PAID', 300);
                if ($invoice->external_id) {
                    \Illuminate\Support\Facades\Cache::put('payment_status_' . $invoice->external_id, 'PAID', 300);
                }

                if ($webhookService) {
                    $webhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.paid');
                }

                return [
                    'success' => true,
                    'is_paid' => true,
                    'status' => 'PAID',
                    'doku_reference' => $dokuReference,
                    'message' => "Pembayaran berhasil diverifikasi oleh DOKU! Ref: {$dokuReference}",
                ];
            }

            return [
                'success' => true,
                'is_paid' => false,
                'status' => $transaction->status,
                'message' => 'DOKU melaporkan pembayaran belum diselesaikan (PENDING).',
            ];
        } catch (\Throwable $e) {
            Log::error("syncTransactionWithDoku exception: " . $e->getMessage());
            return [
                'success' => false,
                'is_paid' => false,
                'status' => $transaction->status,
                'message' => 'Gagal menghubungi DOKU: ' . $e->getMessage(),
            ];
        }
    }

    public function cancelPayment(string $reference): bool
    {
        return true;
    }
}
