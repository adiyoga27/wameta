<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationLabel extends Model
{
    protected $fillable = ['device_id', 'contact_number', 'chat_label_id'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function chatLabel()
    {
        return $this->belongsTo(ChatLabel::class);
    }
}
