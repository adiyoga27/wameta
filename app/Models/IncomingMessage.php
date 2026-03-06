<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingMessage extends Model
{
    protected $fillable = ['device_id', 'from_number', 'from_name', 'message_type', 'message_body', 'media_url', 'wa_message_id', 'wa_timestamp'];

    protected $casts = ['wa_timestamp' => 'datetime'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
