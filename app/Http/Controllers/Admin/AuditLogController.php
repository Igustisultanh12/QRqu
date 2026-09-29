<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $auditLogs = AuditLog::with('user')->latest()->paginate(20, ['*'], 'audit_page');
        $securityLogs = SecurityLog::with(['user', 'customer'])->latest()->paginate(20, ['*'], 'security_page');

        return Inertia::render('Admin/AuditLogs/Index', [
            'auditLogs' => $auditLogs,
            'securityLogs' => $securityLogs,
        ]);
    }
}
