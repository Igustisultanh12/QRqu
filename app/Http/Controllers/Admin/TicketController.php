<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Ticket::with(['user', 'customer', 'replies.user'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'OPEN')->count(),
            'in_progress' => Ticket::where('status', 'IN_PROGRESS')->count(),
            'resolved' => Ticket::where('status', 'RESOLVED')->count(),
            'closed' => Ticket::where('status', 'CLOSED')->count(),
        ];

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'category' => $request->category ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
            'admin_reply' => 'required|string|min:3',
        ], [
            'status.required' => 'Pilih status tiket.',
            'admin_reply.required' => 'Pesan tanggapan admin wajib diisi.',
            'admin_reply.min' => 'Pesan tanggapan admin minimal 3 karakter.',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => $request->status,
            'admin_reply' => $request->admin_reply,
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->admin_reply,
            'is_admin' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'ticket.replied',
            'model_type' => Ticket::class,
            'model_id' => (string) $ticket->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $request->status, 'admin_reply' => $request->admin_reply],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Tiket {$ticket->ticket_number} berhasil ditanggapi dan status diubah ke: {$request->status}.");
    }
}
