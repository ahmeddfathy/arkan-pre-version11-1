<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectField extends Model
{
    protected $fillable = [
        'name',
        'field_key',
        'field_type',
        'field_options',
        'is_required',
        'is_active',
        'order',
        'description'
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope للحقول النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للحقول مرتبة حسب الترتيب
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * الحصول على الحقول النشطة مرتبة
     */
    public static function getActiveFields()
    {
        return self::active()->ordered()->get();
    }
}
