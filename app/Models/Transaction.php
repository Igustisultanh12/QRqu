<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoice_id',
        'customer_id',
        'external_id',
        'amount',
        'status',
        'previous_status',
        'status_changed_at',
        'doku_reference',
        'payment_gateway_ref',
        'doku_request_id',
        'doku_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status_changed_at' => 'datetime',
            'doku_response' => 'array',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(TransactionStatusHistory::class);
    }

    public function dokuTransaction()
    {
        return $this->hasOne(DokuTransaction::class);
    }

    public static function generateId(): string
    {
        return 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(8));
    }

    /**
     * State machine validation and transition
     */
    public function transitionTo(string $targetStatus, string $trigger, ?string $reason = null, array $metadata = []): bool
    {
        $currentStatus = $this->status;
        $targetStatus = strtoupper($targetStatus);

        // Terminal states cannot transition to other states except REFUNDED from PAID
        if ($currentStatus === 'PAID' && $targetStatus !== 'REFUNDED') {
            return false;
        }

        if (in_array($currentStatus, ['EXPIRED', 'FAILED', 'CANCELLED', 'REFUNDED'])) {
            return false;
        }

        $allowedTransitions = [
            'CREATED' => ['PENDING', 'FAILED', 'CANCELLED'],
            'PENDING' => ['PAID', 'FAILED', 'EXPIRED', 'CANCELLED'],
            'PAID' => ['REFUNDED'],
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($targetStatus, $allowedTransitions[$currentStatus])) {
            return false;
        }

        $this->previous_status = $currentStatus;
        $this->status = $targetStatus;
        $this->status_changed_at = now();
        $this->save();

        // Update invoice status synchronously
        if ($this->invoice) {
            $invoiceUpdates = ['status' => $targetStatus];
            if ($targetStatus === 'PAID') {
                $invoiceUpdates['paid_at'] = now();
            }
            $this->invoice->update($invoiceUpdates);
        }

        // Record history
        $this->statusHistories()->create([
            'from_status' => $currentStatus,
            'to_status' => $targetStatus,
            'trigger' => $trigger,
            'reason' => $reason,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        return true;
    }
}
