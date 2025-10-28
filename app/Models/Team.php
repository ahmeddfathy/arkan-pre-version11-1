<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class Team extends JetstreamTeam
{

    use HasFactory, HasSecureId, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'personal_team', 'user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء فريق جديد',
                'updated' => 'تم تحديث بيانات الفريق',
                'deleted' => 'تم حذف الفريق',
                default => $eventName
            });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * Transfer ownership of the team to another user.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function transferOwnership(User $user): void
    {
        // If this is a personal team being transferred, update personal_team flag
        if ($this->personal_team) {
            // Set all other teams owned by the target user to non-personal
            $user->ownedTeams()->where('personal_team', true)->update(['personal_team' => false]);

            // Set the current owner's other personal team if they have one
            $oldOwner = User::find($this->user_id);
            if ($oldOwner && $oldOwner->ownedTeams()->count() > 1) {
                $oldOwner->ownedTeams()->where('id', '!=', $this->id)->first()->forceFill([
                    'personal_team' => true,
                ])->save();
            }
        }

        $this->forceFill([
            'user_id' => $user->id,
        ])->save();

        $this->load('owner');
    }
}
