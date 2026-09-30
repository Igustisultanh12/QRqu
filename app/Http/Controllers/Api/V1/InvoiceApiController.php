<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceApiController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Create Invoice (POST /api/v1/invoices)
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $validator = Validator::make($request->all(), [
            'external_id' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1|max:100000000',
            'description' => 'nullable|string|max:255',
            'store_id' => 'nullable|integer',
            'customer' => 'nullable|array',
            'customer.name' => 'nullable|string|max:100',
            'customer.email' => 'nullable|email|max:100',
            'customer.phone' => 'nullable|string|max:25',
            'callback_url' => 'nullable|url|max:255',
            'webhook_url' => 'nullable|url|max:255',
            'expired_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The provided request payload is invalid',
                    'details' => $validator->errors(),
                ],
                'request_id' => $request->attributes->get('request_id'),
            ], 422);
        }

        $invoiceData = $validator->validated();
        $credential = $request->attributes->get('credential');
        if (empty($invoiceData['store_id']) && !empty($credential?->store_id)) {
            $invoiceData['store_id'] = $credential->store_id;
        }

        $result = $this->invoiceService->createInvoice($customer, $invoiceData);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'request_id' => $request->attributes->get('request_id'),
            ], 400);
        }

        /** @var Invoice $invoice */
        $invoice = $result['invoice'];

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $invoice->id,
                'external_id' => $invoice->external_id,
                'amount' => (float) $invoice->amount,
                'status' => $invoice->status,
                'payment_method' => $invoice->payment_method,
                'qr_string' => $invoice->qr_string,
                'qr_url' => $invoice->qr_url ?: url("/checkout/{$invoice->id}"),
                'expired_at' => $invoice->expired_at?->toIso8601String(),
            ],
            'request_id' => $request->attributes->get('request_id'),
        ], 201);
    }

    /**
     * Get Invoice by ID or external_id (GET /api/v1/invoices/{invoice})
     */
    public function show(Request $request, string $invoiceId): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $invoice = Invoice::where('customer_id', $customer->id)
            ->where(function ($query) use ($invoiceId) {
                $query->where('id', $invoiceId)
                    ->orWhere('external_id', $invoiceId);
            })
            ->with('latestTransaction')
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVOICE_NOT_FOUND',
                    'message' => "Invoice '{$invoiceId}' not found for this account",
                ],
                'request_id' => $request->attributes->get('request_id'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $invoice->id,
                'external_id' => $invoice->external_id,
                'amount' => (float) $invoice->amount,
                'status' => $invoice->status,
                'description' => $invoice->description,
                'payment_method' => $invoice->payment_method,
                'qr_string' => $invoice->qr_string,
                'qr_url' => $invoice->qr_url ?: url("/checkout/{$invoice->id}"),
                'created_at' => $invoice->created_at->toIso8601String(),
                'expired_at' => $invoice->expired_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'transaction' => $invoice->latestTransaction ? [
                    'transaction_id' => $invoice->latestTransaction->id,
                    'status' => $invoice->latestTransaction->status,
                    'reference' => $invoice->latestTransaction->doku_reference,
                ] : null,
            ],
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }

    /**
     * Cancel an invoice (POST /api/v1/invoices/{invoice}/cancel)
     */
    public function cancel(Request $request, string $invoiceId): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $invoice = Invoice::where('customer_id', $customer->id)
            ->where('id', $invoiceId)
            ->first();

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVOICE_NOT_FOUND',
                    'message' => "Invoice '{$invoiceId}' not found",
                ],
                'request_id' => $request->attributes->get('request_id'),
            ], 404);
        }

        if (!$invoice->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => "Invoice cannot be cancelled because its current status is {$invoice->status}",
                ],
                'request_id' => $request->attributes->get('request_id'),
            ], 400);
        }

        $cancelled = $this->invoiceService->cancelInvoice($customer, $invoice);

        return response()->json([
            'success' => $cancelled,
            'data' => [
                'invoice_id' => $invoice->id,
                'status' => 'CANCELLED',
                'message' => 'Invoice has been successfully cancelled',
            ],
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }

    /**
     * List Transactions (GET /api/v1/transactions)
     */
    public function transactions(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $query = Transaction::where('customer_id', $customer->id)
            ->with('invoice')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }

        if ($request->filled('external_id')) {
            $query->where('external_id', $request->query('external_id'));
        }

        $perPage = min((int) ($request->query('per_page', 20)), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }

    /**
     * Show Transaction Details (GET /api/v1/transactions/{transaction})
     */
    public function showTransaction(Request $request, string $transactionId): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $transaction = Transaction::where('customer_id', $customer->id)
            ->where('id', $transactionId)
            ->with(['invoice', 'statusHistories'])
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSACTION_NOT_FOUND',
                    'message' => "Transaction '{$transactionId}' not found",
                ],
                'request_id' => $request->attributes->get('request_id'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'request_id' => $request->attributes->get('request_id'),
        ]);
    }
}
