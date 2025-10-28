<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class Message extends Model
{
    use HasSecureId;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_seen'
    ];

    protected $casts = [
        'is_seen' => 'boolean',
        'content' => SafeEncryptedCast::class,
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
