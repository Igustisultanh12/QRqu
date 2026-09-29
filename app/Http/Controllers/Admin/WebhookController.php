<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\WebhookDelivery;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function index(Request $request): Response
    {
        $query = WebhookDelivery::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->query('event'));
        }

        $deliveries = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Webhooks/Index', [
            'deliveries' => $deliveries,
            'filters' => $request->only(['status', 'event']),
        ]);
    }

    public function retry(Request $request, WebhookDelivery $delivery, CustomerWebhookService $service): RedirectResponse
    {
        $service->retryDelivery($delivery);
        AuditLog::record('ADMIN_RETRY_WEBHOOK', $delivery);

        return redirect()->back()->with('success', "Webhook #{$delivery->id} berhasil dimasukkan ulang ke antrean pengiriman.");
    }
}
