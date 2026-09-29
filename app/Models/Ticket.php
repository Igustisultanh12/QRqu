<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'customer_id',
        'subject',
        'category',
        'priority',
        'status',
        'description',
        'admin_reply',
    ];

    public static function generateTicketNumber(): string
    {
        return 'TCK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }
}
