<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = ['device_id', 'user_id', 'message_template_id', 'name', 'status', 'total', 'sent', 'delivered', 'read', 'failed'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messageTemplate()
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function broadcastContacts()
    {
        return $this->hasMany(BroadcastContact::class);
    }
}
