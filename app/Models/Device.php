<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['name', 'waba_id', 'phone_number_id', 'access_token', 'app_id', 'app_secret', 'webhook_verify_token', 'is_active', 'pricing_marketing', 'pricing_utility', 'pricing_authentication', 'pricing_service'];

    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'device_user')->withTimestamps();
    }

    public function messageTemplates()
    {
        return $this->hasMany(MessageTemplate::class);
    }

    public function broadcasts()
    {
        return $this->hasMany(Broadcast::class);
    }

    public function incomingMessages()
    {
        return $this->hasMany(IncomingMessage::class);
    }
}
