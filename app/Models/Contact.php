<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['user_id', 'phone', 'name', 'tags'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function broadcastContacts()
    {
        return $this->hasMany(BroadcastContact::class);
    }
}
