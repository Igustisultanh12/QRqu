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
        if ($customer) {
            $customer->ensureStores();
        }

        $webhooks = $customer ? $customer->webhooks()->with('store')->latest()->get() : collect();

        // Ensure backward compatibility: if existing webhook has null name, default it
        foreach ($webhooks as $wh) {
            if (empty($wh->name)) {
                $wh->name = 'Endpoint Webhook Utama';
            }
        }

        $stores = $customer ? $customer->stores()->orderBy('is_default', 'desc')->orderBy('name')->get() : collect();

        $deliveries = $customer ? WebhookDelivery::where('customer_id', $customer->id)
            ->latest()
            ->paginate(15) : null;

        return Inertia::render('Customer/Webhooks/Index', [
            'webhooks' => $webhooks,
            'stores' => $stores,
            'deliveries' => $deliveries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customer;
        if (!$customer) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'url' => 'required|url|max:255',
            'store_id' => 'nullable|exists:stores,id',
            'secret' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ], [
            'url.required' => 'URL Webhook wajib diisi.',
            'url.url' => 'Format URL Webhook tidak valid (harus diawali http:// atau https://).',
        ]);

        $name = !empty($validated['name']) ? $validated['name'] : 'Webhook #' . ($customer->webhooks()->count() + 1);
        $secret = !empty($validated['secret']) ? $validated['secret'] : Webhook::generateSecret();
        $storeId = !empty($validated['store_id']) ? (int) $validated['store_id'] : null;

        if ($storeId) {
            $storeExists = $customer->stores()->where('id', $storeId)->exists();
            if (!$storeExists) {
                $storeId = null;
            }
        }

        $webhook = Webhook::create([
            'customer_id' => $customer->id,
            'name' => $name,
            'store_id' => $storeId,
            'url' => $validated['url'],
            'secret' => $secret,
            'events' => ['payment.paid', 'payment.failed', 'payment.expired'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record('CREATE_WEBHOOK_ENDPOINT', $webhook, null, ['name' => $webhook->name, 'url' => $webhook->url]);

        return redirect()->back()->with('success', "Endpoint Webhook \"{$webhook->name}\" berhasil ditambahkan!");
    }

    public function update(Request $request, Webhook $webhook): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($webhook->customer_id !== $customer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'url' => 'required|url|max:255',
            'store_id' => 'nullable|exists:stores,id',
            'secret' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ], [
            'url.required' => 'URL Webhook wajib diisi.',
            'url.url' => 'Format URL Webhook tidak valid.',
        ]);

        $storeId = !empty($validated['store_id']) ? (int) $validated['store_id'] : null;
        if ($storeId) {
            $storeExists = $customer->stores()->where('id', $storeId)->exists();
            if (!$storeExists) {
                $storeId = null;
            }
        }

        $webhook->update([
            'name' => !empty($validated['name']) ? $validated['name'] : $webhook->name,
            'store_id' => $storeId,
            'url' => $validated['url'],
            'secret' => !empty($validated['secret']) ? $validated['secret'] : $webhook->secret,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::record('UPDATE_WEBHOOK_ENDPOINT', $webhook, null, ['name' => $webhook->name, 'url' => $webhook->url]);

        return redirect()->back()->with('success', "Endpoint Webhook \"{$webhook->name}\" berhasil diperbarui!");
    }

    public function destroy(Request $request, Webhook $webhook): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($webhook->customer_id !== $customer->id) {
            abort(403);
        }

        $name = $webhook->name ?: 'Webhook';
        $webhook->delete();

        return redirect()->back()->with('success', "Endpoint Webhook \"{$name}\" berhasil dihapus!");
    }

    public function toggle(Request $request, Webhook $webhook): RedirectResponse
    {
        $customer = $request->user()->customer;
        if ($webhook->customer_id !== $customer->id) {
            abort(403);
        }

        $webhook->is_active = !$webhook->is_active;
        $webhook->save();

        $statusLabel = $webhook->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Webhook \"{$webhook->name}\" berhasil {$statusLabel}!");
    }

    public function testPing(Request $request, CustomerWebhookService $service): RedirectResponse
    {
        $customer = $request->user()->customer;
        $webhookId = $request->input('webhook_id');

        if ($webhookId) {
            $webhook = Webhook::where('customer_id', $customer->id)->find($webhookId);
        } else {
            $webhook = Webhook::where('customer_id', $customer->id)->where('is_active', true)->first();
        }

        if (!$webhook) {
            return redirect()->back()->with('error', 'Harap tambahkan URL webhook terlebih dahulu sebelum melakukan test ping.');
        }

        $eventId = 'EVT-TEST-' . strtoupper(Str::random(16));
        $payload = [
            'event' => 'ping.test',
            'event_id' => $eventId,
            'webhook_name' => $webhook->name,
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
            return redirect()->back()->with('success', "Test Webhook ke \"{$webhook->name}\" ({$webhook->url}) berhasil terkirim! Respon HTTP {$delivery->http_status} ({$delivery->duration_ms}ms).");
        } else {
            $errDetail = $delivery->http_status ? "HTTP {$delivery->http_status}" : 'Koneksi gagal';
            $snippet = $delivery->response_body ? ": " . Str::limit(strip_tags($delivery->response_body), 120) : '';
            return redirect()->back()->with('error', "Test Webhook ke \"{$webhook->name}\" gagal ({$errDetail}){$snippet}. Cek detail log di bawah.");
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
