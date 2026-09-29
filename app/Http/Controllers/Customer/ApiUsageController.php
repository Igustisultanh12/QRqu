<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\ApiUsage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiUsageController extends Controller
{
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;

        $usages = ApiUsage::where('customer_id', $customer->id)
            ->latest('date')
            ->limit(30)
            ->get();

        $logs = ApiLog::where('customer_id', $customer->id)
            ->latest()
            ->paginate(20);

        $plan = $customer->activeSubscription?->plan;

        return Inertia::render('Customer/ApiUsage/Index', [
            'usages' => $usages,
            'logs' => $logs,
            'rate_limit_rpm' => $customer->getRateLimitRpm(),
            'monthly_limit' => $plan ? $plan->api_limit : 10000,
        ]);
    }
}
