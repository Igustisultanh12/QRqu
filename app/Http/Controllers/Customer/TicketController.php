<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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
        $user = $request->user();

        $tickets = Ticket::where('user_id', $user->id)
            ->with(['replies.user'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Customer/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string|min:10',
        ], [
            'category.required' => 'Kategori bantuan wajib dipilih.',
            'description.required' => 'Rincian keluhan wajib diisi.',
            'description.min' => 'Rincian keluhan minimal 10 karakter.',
        ]);

        $user = $request->user();
        if ($user->isAdmin() && !$user->customer) {
            $user->ensureCustomerProfile();
        }
        $customer = $user->customer;

        $ticketNumber = Ticket::generateTicketNumber();
        $priority = in_array($request->category, ['Penarikan Saldo / Settlement', 'Kendala Transaksi QRIS']) ? 'HIGH' : 'MEDIUM';

        Ticket::create([
            'user_id' => $user->id,
            'customer_id' => $customer?->id,
            'ticket_number' => $ticketNumber,
            'category' => $request->category,
            'subject' => $request->subject ?: $request->category,
            'priority' => $priority,
            'status' => 'OPEN',
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', "Tiket bantuan berhasil dibuat dengan nomor referensi: {$ticketNumber}. Tim kami akan segera meninjaunya.");
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        // Pastikan tiket milik user yang sedang login
        if ($ticket->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $ticket->load(['replies.user', 'customer', 'user']);

        return Inertia::render('Customer/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|min:3',
        ], [
            'message.required' => 'Pesan balasan tidak boleh kosong.',
            'message.min' => 'Pesan balasan minimal 3 karakter.',
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_admin' => false,
        ]);

        // Jika tiket sudah RESOLVED / CLOSED, buka kembali jadi OPEN saat user balas
        if (in_array($ticket->status, ['RESOLVED', 'CLOSED'])) {
            $ticket->update(['status' => 'OPEN']);
        }

        return redirect()->back()->with('success', 'Balasan Anda berhasil dikirim ke customer service.');
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403);
        }

        $ticket->update(['status' => 'CLOSED']);

        return redirect()->back()->with('success', "Tiket {$ticket->ticket_number} berhasil ditutup.");
    }
}
