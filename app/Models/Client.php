<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Casts\SafeEncryptedArrayCast;
use App\Traits\HasSecureId;

class Client extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'name',
        'emails',
        'phones',
        'company_name',
        'client_code',
        'source',
        'interests',
        'logo',
    ];

    protected $casts = [
        'emails' => SafeEncryptedArrayCast::class,
        'phones' => SafeEncryptedArrayCast::class,
        'interests' => 'array',
        'name' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'emails', 'phones', 'company_name', 'client_code',
                'source', 'interests', 'logo'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء عميل جديد',
                'updated' => 'تم تحديث بيانات العميل',
                'deleted' => 'تم حذف العميل',
                default => $eventName
            });
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($client) {
            if (empty($client->client_code)) {
                $client->client_code = self::generateClientCode();
            }
            if (is_string($client->emails)) {
                $client->emails = array_filter(array_map('trim', explode(',', $client->emails)));
            }
            if (is_string($client->phones)) {
                $client->phones = array_filter(array_map('trim', explode(',', $client->phones)));
            }
        });
        static::updating(function ($client) {
            if (is_string($client->emails)) {
                $client->emails = array_filter(array_map('trim', explode(',', $client->emails)));
            }
            if (is_string($client->phones)) {
                $client->phones = array_filter(array_map('trim', explode(',', $client->phones)));
            }
        });
    }

    public static function generateClientCode()
    {
        do {
            $code = 'CL-2024-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('client_code', $code)->exists());
        return $code;
    }

    // Existing relationships
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    // New CRM relationships
    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    public function tickets()
    {
        return $this->hasManyThrough(
            ClientTicket::class,
            Project::class,
            'client_id', // Foreign key on projects table
            'project_id', // Foreign key on client_tickets table
            'id', // Local key on clients table
            'id' // Local key on projects table
        );
    }

    // Computed attributes
    public function getTotalPointsAttribute()
    {
        return $this->projects()->sum('total_points');
    }

    public function getTotalServicesCountAttribute()
    {
        $serviceIds = [];
        foreach ($this->projects as $project) {
            $serviceIds = array_merge($serviceIds, $project->services->pluck('id')->toArray());
        }
        return count(array_unique($serviceIds));
    }

    // New CRM methods
    public function getLastCallLogAttribute()
    {
        return $this->callLogs()->latest('call_date')->first();
    }

    public function getOpenTicketsCountAttribute()
    {
        return $this->tickets()->whereIn('client_tickets.status', ['open', 'in_progress'])->count();
    }

    public function getResolvedTicketsCountAttribute()
    {
        return $this->tickets()->where('client_tickets.status', 'resolved')->count();
    }

    public function addInterest($interest)
    {
        $interests = $this->interests ?? [];
        if (!in_array($interest, $interests)) {
            $interests[] = $interest;
            $this->update(['interests' => $interests]);
        }
    }

    public function removeInterest($interest)
    {
        $interests = $this->interests ?? [];
        $interests = array_filter($interests, function($item) use ($interest) {
            return $item !== $interest;
        });
        $this->update(['interests' => array_values($interests)]);
    }
}
