<?php

namespace App\Http\Livewire\Teams;

use App\Models\User;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager as JetstreamTeamMemberManager;

class TeamMemberManager extends JetstreamTeamMemberManager
{
    /**
     * The available users for adding to the team.
     *
     * @var \Illuminate\Support\Collection
     */
    public $availableUsers;

    /**
     * Mount the component.
     *
     * @param  mixed  $team
     * @return void
     */
    public function mount($team)
    {
        parent::mount($team);

        // Load all users who are not already in the team
        $this->availableUsers = User::whereNotIn('id', $team->users->pluck('id'))->get();
    }

    /**
     * Handle a user selection from the dropdown.
     *
     * @param  string  $userId
     * @return void
     */
    public function selectUser($userId)
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->addTeamMemberForm['email'] = $user->email;
            }
        } else {
            $this->addTeamMemberForm['email'] = '';
        }
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('teams.team-member-manager');
    }
}
