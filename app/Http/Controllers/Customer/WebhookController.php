<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->fresh()->customer;
        $webhook = $customer ? Webhook::where('customer_id', $customer->id)->first() : null;

        $deliveries = $customer ? WebhookDelivery::where('customer_id', $customer->id)
            ->latest()
            ->paginate(15) : null;

        return Inertia::render('Customer/Webhooks/Index', [
            'webhook' => $webhook,
            'deliveries' => $deliveries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'url' => 'required|url|max:255',
        ]);

        $customer = $request->user()->customer;

        $webhook = Webhook::updateOrCreate(
            ['customer_id' => $customer->id],
            [
                'url' => $request->input('url'),
                'secret' => $request->input('secret') ?: Webhook::generateSecret(),
                'events' => ['payment.paid', 'payment.failed', 'payment.expired'],
                'is_active' => true,
            ]
        );

        AuditLog::record('UPDATE_WEBHOOK_CONFIG', $webhook, null, ['url' => $webhook->url]);

        return redirect()->back()->with('success', 'Pengaturan Webhook berhasil disimpan!');
    }

    public function testPing(Request $request, CustomerWebhookService $service): RedirectResponse
    {
        $customer = $request->user()->customer;
        $webhook = Webhook::where('customer_id', $customer->id)->first();

        if (!$webhook) {
            return redirect()->back()->with('error', 'Harap simpan URL webhook terlebih dahulu sebelum melakukan test ping.');
        }

        $eventId = 'EVT-TEST-' . strtoupper(Str::random(16));
        $payload = [
            'event' => 'ping.test',
            'event_id' => $eventId,
            'message' => 'Ini adalah tes tembakan webhook dari QRqu Payment Gateway',
            'timestamp' => time(),
        ];

        $rawJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $rawJson, $webhook->secret);

        $delivery = WebhookDelivery::create([
            'customer_id' => $customer->id,
            'transaction_id' => null,
            'invoice_id' => null,
            'event_id' => $eventId,
            'event' => 'ping.test',
            'url' => $webhook->url,
            'payload' => $payload,
            'signature' => $signature,
            'attempt' => 0,
            'max_attempts' => 1,
            'status' => 'PENDING',
        ]);

        $delivery = $service->executeDelivery($delivery);

        if ($delivery->status === 'DELIVERED') {
            return redirect()->back()->with('success', "Test Webhook berhasil terkirim! Respon HTTP {$delivery->http_status} ({$delivery->duration_ms}ms).");
        } else {
            $errDetail = $delivery->http_status ? "HTTP {$delivery->http_status}" : 'Koneksi gagal';
            $snippet = $delivery->response_body ? ": " . Str::limit(strip_tags($delivery->response_body), 120) : '';
            return redirect()->back()->with('error', "Test Webhook gagal ({$errDetail}){$snippet}. Cek detail log di bawah.");
        }
    }

    public function retry(Request $request, WebhookDelivery $delivery, CustomerWebhookService $service): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($delivery->customer_id !== $customer->id) {
            abort(403);
        }

        $delivery = $service->retryDelivery($delivery);

        if ($delivery->status === 'DELIVERED') {
            return redirect()->back()->with('success', "Webhook berhasil dikirim ulang! Respon HTTP {$delivery->http_status} ({$delivery->duration_ms}ms).");
        } else {
            $errDetail = $delivery->http_status ? "HTTP {$delivery->http_status}" : 'Koneksi gagal';
            $snippet = $delivery->response_body ? ": " . Str::limit(strip_tags($delivery->response_body), 120) : '';
            return redirect()->back()->with('error', "Kirim ulang gagal ({$errDetail}){$snippet}.");
        }
    }
}
