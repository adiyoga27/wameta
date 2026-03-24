<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLabel extends Model
{
    protected $fillable = ['device_id', 'name', 'color_hex'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
