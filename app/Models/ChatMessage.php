<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'device_id',
        'contact_number',
        'contact_name',
        'direction',
        'message_type',
        'message_body',
        'media_url',
        'wa_message_id',
        'wa_timestamp',
        'is_read',
    ];

    protected $casts = [
        'wa_timestamp' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function chatLabel()
    {
        return $this->belongsTo(ChatLabel::class);
    }
}
