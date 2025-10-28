<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class SalarySheet extends Model
{
    use HasSecureId;

    protected $fillable = [
        'employee_id',
        'month',
        'file_path',
        'original_filename'
    ];

    protected $casts = [
        'file_path' => SafeEncryptedCast::class,
        'original_filename' => SafeEncryptedCast::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
