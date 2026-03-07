<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'device_id',
        'event_type',
        'phone_number_id',
        'payload',
        'processed',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
