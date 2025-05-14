<?php

use Livewire\Livewire;
use App\Livewire\GameDashboard;
use Livewire\Features\SupportTesting\Testable as LivewireTest;

/*
    This is for Livewire Test Components used in *multiple* challenges
    because we cannot use the same function name across multiple files
*/

function swapTeam($player, $team_id): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('challenge_properties.team_id', $team_id)
        ->call('callChallengeAction', 'swapTeams');
}
