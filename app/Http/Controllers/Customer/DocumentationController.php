<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = $request->user()?->customer;
        $activeCredential = $customer ? $customer->apiCredentials()->where('status', 'active')->first() : null;

        return Inertia::render('Customer/Documentation/Index', [
            'sample_api_key' => $activeCredential ? $activeCredential->api_key : 'qrqu_live_example1234567890',
            'base_api_url' => url('/api/v1'),
            'tolerance_seconds' => (int) env('QRQU_API_TIMESTAMP_TOLERANCE', 300),
        ]);
    }
}
