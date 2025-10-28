<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\HasSecureId;

class MeetingParticipant extends Pivot
{
    use HasSecureId;

    public $incrementing = true;


    protected $fillable = [
        'meeting_id',
        'user_id',
        'attended',
    ];


    protected $casts = [
        'attended' => 'boolean',
    ];


    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
