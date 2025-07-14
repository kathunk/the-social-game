<?php

namespace App\Events;

use App\Challenges\Dtos\ChallengeData;
use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasChallenge;
use App\Models\Challenge;
use App\Models\Game;
use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Event;

class ChallengeStarted extends Event
{
    use HasActiveGame, HasChallenge;

    public ?ChallengeData $challenge_data = null;

    /**
     * Flag to track if the challenge data has been properly converted to array format
     */
    protected bool $challenge_data_converted = false;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->challenges()->filter(fn ($c) => $c->status === 'active')->count() === 0,
            'Cannot have more than one active challenge'
        );
    }

    public function boot(ChallengeState $challenge, GameState $game)
    {
        $handlerClass = $challenge->handler();
        $dtoClass = $handlerClass->challenge_data_class;

        if (!$dtoClass) {
            return;
        }

        $this->challenge_data = $dtoClass::fromGameAndChallenge(
            game: $game,
            challenge: $challenge,
        );

        // Store the challenge data in the event for deterministic usage
        $this->challenge_data_converted = true;
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->status = 'active';

        // If we have challenge_data from DTO, convert it to array format
        if ($this->challenge_data) {
            $challenge->challenge_data = $this->convertChallengeDataToArray();
        } else {
            // Fall back to the old method for backward compatibility
            $challenge->challenge_data = $challenge->handler()->dataArrayForState();
        }
    }

    /**
     * Convert the DTO challenge data to array format
     *
     * @return array
     */
    protected function convertChallengeDataToArray(): array
    {
        // Get all public properties of the DTO
        $reflection = new \ReflectionClass($this->challenge_data);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $data[$name] = $this->challenge_data->{$name};
        }

        return $data;
    }

    public function applyToGame(GameState $state)
    {
        $state->current_challenge_id = $this->challenge_id;

        // Only call onChallengeStarted for challenge handlers that don't use DTOs
        // This maintains backward compatibility while ensuring determinism
        if (!$this->challenge_data) {
            $this->state(ChallengeState::class)->handler()->onChallengeStarted($state);
        }
    }

    public function handle(ChallengeState $state)
    {
        $game = Game::find($this->game_id);
        $game->update(['current_challenge_id' => $this->challenge_id]);

        $updateData = [
            'status' => 'active',
            'challenge_data' => $state->challenge_data,
        ];

        // Store the DTO as JSON data if available for debugging and future migration
        if ($this->challenge_data) {
            // You might want to add a column to store the serialized DTO
            // This is commented out as it would require a schema change
            // $updateData['challenge_data_dto'] = serialize($this->challenge_data);
        }

        Challenge::find($this->challenge_id)->update($updateData);
    }
}
