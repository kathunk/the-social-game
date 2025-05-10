<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;

class TeamPage extends Component
{
    public Game $game;
    public Team $team;

    #[Computed]
    public function players()
    {
        return $this->team->players;
    }

    #[Computed]
    public function scoreHistoryEntries(): array
    {
        return array_reverse($this->team->state()->score_history);
    }

    public function mount(string $snowflake, Team $team)
    {
        Log::debug('TeamPage mount', [
            'snowflake' => $snowflake,
            'team_id' => $team->id,
            'game_exists' => Game::where('id', $snowflake)->exists(),
            'team_game_id' => $team->game_id ?? 'unknown'
        ]);

        // First check if game exists
        if (!Game::where('id', $snowflake)->exists()) {
            Log::debug('Game does not exist, redirecting');
            return redirect()->route('dashboard');
        }

        try {
            $this->game = Game::findOrFail($snowflake);

            // Check if team belongs to this game
            if ($team->game_id != $snowflake) {
                Log::debug('Team does not belong to this game, redirecting');
                return redirect()->route('dashboard');
            }

            $this->team = $team;
        } catch (\Exception $e) {
            Log::error('Error mounting TeamPage', [
                'error' => $e->getMessage(),
                'snowflake' => $snowflake
            ]);
            return redirect()->route('dashboard');
        }
    }

    public function render()
    {
        return view('livewire.team-page');
    }
}
