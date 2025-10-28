<?php

namespace App\Http\Livewire\Teams;

use Livewire\Component;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Jetstream;

class TransferTeamOwnership extends Component
{
    /**
     * The team instance.
     *
     * @var \App\Models\Team
     */
    public $team;

    /**
     * The ID of the user to transfer ownership to.
     *
     * @var string
     */
    public $selectedUserId = '';

    /**
     * Indicates if team ownership transfer is being confirmed.
     *
     * @var bool
     */
    public $confirmingTeamOwnershipTransfer = false;

    /**
     * Mount the component.
     *
     * @param  mixed  $team
     * @return void
     */
    public function mount($team)
    {
        $this->team = $team;
    }

    /**
     * Confirm that the user wants to transfer team ownership.
     *
     * @return void
     */
    public function confirmTeamOwnershipTransfer()
    {
        $this->confirmingTeamOwnershipTransfer = true;
    }

    /**
     * Transfer team ownership.
     *
     * @return void
     */
    public function transferOwnership()
    {
        if (! Gate::check('transferTeamOwnership', $this->team)) {
            abort(403);
        }

        $user = User::findOrFail($this->selectedUserId);
        $currentUser = Auth::user();
        $teamWasPersonal = $this->team->personal_team;
        $oldTeamId = $this->team->id;

        // Transfer team ownership
        $this->team->transferOwnership($user);

        // Reset the form
        $this->selectedUserId = '';
        $this->confirmingTeamOwnershipTransfer = false;

        // Refresh user and team data
        $this->team->refresh();

        session()->flash('flash.banner', 'تم نقل ملكية الفريق بنجاح');
        session()->flash('flash.bannerStyle', 'success');

        $this->dispatch('transferred');

        // If the current user is no longer the owner and this was their current team
        if (Auth::id() !== $this->team->user_id && $currentUser->current_team_id == $oldTeamId) {
            // Find another team to switch to
            $personalTeam = Team::where('user_id', $currentUser->id)
                ->where('personal_team', true)
                ->first();

            $otherTeam = Team::where('user_id', $currentUser->id)
                ->first();

            $teamInMember = DB::table('team_user')
                ->where('user_id', $currentUser->id)
                ->first();

            $newTeamId = null;

            if ($personalTeam) {
                $newTeamId = $personalTeam->id;
            } elseif ($otherTeam) {
                $newTeamId = $otherTeam->id;
            } elseif ($teamInMember) {
                $newTeamId = $teamInMember->team_id;
            }

            if ($newTeamId) {
                // Update user's current team in database
                DB::table('users')
                    ->where('id', $currentUser->id)
                    ->update(['current_team_id' => $newTeamId]);

                // Update session
                $newTeam = Team::find($newTeamId);
                session()->put('currentTeam', $newTeam);
            } else {
                // If no other teams available, set current_team_id to null
                DB::table('users')
                    ->where('id', $currentUser->id)
                    ->update(['current_team_id' => null]);

                // Clear current team from session
                session()->forget('currentTeam');
            }

            return redirect()->route('dashboard');
        }
    }

    /**
     * Get all users except the current team owner.
     */
    public function getAllUsersProperty()
    {
        return User::where('id', '!=', $this->team->owner->id)->get();
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('teams.transfer-team-ownership');
    }
}
