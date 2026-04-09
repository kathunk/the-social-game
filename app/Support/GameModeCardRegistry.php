<?php

namespace App\Support;

use App\Models\GameMode;
use Illuminate\Support\Collection;

/**
 * Registry that maps game modes (or families of game modes) to the
 * blade components used to showcase them on the home page.
 *
 * Each entry describes:
 *  - slug:        the family identifier
 *  - mode_names:  game mode names that belong to this family (case-insensitive
 *                 match against GameMode::name)
 *  - component:   blade component name (under x-game-mode-cards.*) to render
 *  - sort:        display order (lower = first)
 *
 * Modes that don't match any family render with the generic card.
 */
class GameModeCardRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function families(): array
    {
        return [
            [
                'slug' => 'hot-takes',
                'mode_names' => ['Hot Takes', 'Tier List'],
                'component' => 'game-mode-cards.hot-takes',
                'sort' => 10,
            ],
            [
                'slug' => 'pecking-order',
                'mode_names' => ['Pecking Order', 'Blood Oaths', 'King Maker', 'Pyramid Scheme'],
                'component' => 'game-mode-cards.pecking-order',
                'sort' => 20,
            ],
        ];
    }

    /**
     * Group a collection of game modes into an ordered list of "card payloads"
     * ready to render on the home page. Each payload is:
     *  [
     *      'component' => 'game-mode-cards.foo',
     *      'modes'     => Collection<GameMode>,  // one or more
     *      'sort'      => int,
     *  ]
     *
     * Modes not matched by any family are returned individually under the
     * generic component.
     *
     * @param  Collection<int, GameMode>  $modes
     * @return Collection<int, array{component: string, modes: Collection<int, GameMode>, sort: int}>
     */
    public static function groupForDisplay(Collection $modes): Collection
    {
        $payloads = collect();
        $unmatched = $modes->keyBy('id');

        foreach (self::families() as $family) {
            $matched = $modes->filter(function (GameMode $mode) use ($family) {
                return collect($family['mode_names'])
                    ->map(fn ($n) => mb_strtolower($n))
                    ->contains(mb_strtolower($mode->name));
            });

            if ($matched->isEmpty()) {
                continue;
            }

            $payloads->push([
                'component' => $family['component'],
                'modes' => $matched->values(),
                'sort' => $family['sort'],
            ]);

            foreach ($matched as $matched_mode) {
                $unmatched->forget($matched_mode->id);
            }
        }

        // Each unmatched mode renders as its own generic card,
        // sorted to the end (preserving the modes' original order).
        $unmatchedIndex = 100;
        foreach ($unmatched as $mode) {
            $payloads->push([
                'component' => 'game-mode-cards.generic',
                'modes' => collect([$mode]),
                'sort' => $unmatchedIndex++,
            ]);
        }

        return $payloads->sortBy('sort')->values();
    }
}
