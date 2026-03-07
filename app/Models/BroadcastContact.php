<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastContact extends Model
{
    protected $fillable = ['broadcast_id', 'contact_id', 'status', 'wa_message_id', 'error_message', 'is_billed'];

    public function broadcast()
    {
        return $this->belongsTo(Broadcast::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
