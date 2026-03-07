<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['device_id', 'name', 'language', 'category', 'header_type', 'header_content', 'header_media_path', 'body', 'footer', 'buttons', 'status', 'rejected_reason', 'meta_template_id'];

    protected $casts = ['buttons' => 'array'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function broadcasts()
    {
        return $this->hasMany(Broadcast::class);
    }
}
