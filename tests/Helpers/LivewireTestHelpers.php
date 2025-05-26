<?php

use App\Livewire\GameDashboard;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;

/*
    This is for Livewire Test Components used in *multiple* challenges
    because we cannot use the same function name across multiple files
*/

function swapTeam($player, $team_id, $challenge_key): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('round_properties.'.$challenge_key.'.team_id', $team_id)
        ->call('callClassAction', 'swapTeams', 'challenge', $challenge_key);
}
