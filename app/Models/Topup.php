<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
{
    protected $fillable = [
        'device_id',
        'user_id',
        'order_id',
        'amount',
        'payment_type',
        'transaction_id',
        'status',
        'snap_token',
        'redirect_url',
        'midtrans_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'midtrans_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'settlement' || $this->status === 'capture';
    }
}
