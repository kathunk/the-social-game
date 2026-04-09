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
 *  - keywords:    substrings that indicate a mode belongs to this family.
 *                 Match is case-insensitive and ignores spaces/punctuation,
 *                 so "Blood Oaths", "BloodOath", and "blood-oath" all match
 *                 the keyword "bloodoath". Pick keywords specific enough that
 *                 they won't accidentally match unrelated game modes.
 *  - component:   blade component name (under x-game-mode-cards.*) to render
 *  - sort:        display order (lower = first)
 *
 * Modes that don't match any family render with the generic card.
 */
class GameModeCardRegistry
{
    /**
     * Per-variant metadata for families that have multiple variants
     * (e.g. the pecking-order family). Each variant has its own keywords,
     * emoji, tagline, and a sort weight controlling display order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function families(): array
    {
        return [
            [
                'slug' => 'hot-takes',
                'keywords' => ['hottake', 'tierlist'],
                'component' => 'game-mode-cards.hot-takes',
                'sort' => 10,
                'variants' => [],
            ],
            [
                'slug' => 'pecking-order',
                'keywords' => ['peckingorder', 'bloodoath', 'kingmaker', 'pyramidscheme'],
                'component' => 'game-mode-cards.pecking-order',
                'sort' => 20,
                'variants' => [
                    [
                        'keywords' => ['peckingorder'],
                        'emoji' => '🐔',
                        'tagline' => 'The classic. Vote your friends up or down. Predict the votes for hidden points.',
                        'sort' => 0,
                    ],
                    [
                        'keywords' => ['bloodoath', 'oath'],
                        'emoji' => '🩸',
                        'tagline' => 'Secretly ally with one player. Sniff out other alliances to score.',
                        'sort' => 1,
                    ],
                    [
                        'keywords' => ['kingmaker', 'king', 'maker', 'crown'],
                        'emoji' => '👑',
                        'tagline' => 'Resign early to give your points away. Anoint a king or nuke your enemies.',
                        'sort' => 2,
                    ],
                    [
                        'keywords' => ['pyramidscheme', 'pyramid', 'scheme'],
                        'emoji' => '🔺',
                        'tagline' => 'Recruit, refer, get in early. A pyramid scheme that can\'t go wrong.',
                        'sort' => 3,
                    ],
                ],
            ],
        ];
    }

    /**
     * Look up the variant metadata for a mode within a family. Returns the
     * default fallback if no variant matches.
     *
     * @return array{emoji: string, tagline: string, sort: int}
     */
    public static function variantMetaForMode(string $familySlug, GameMode $mode): array
    {
        $family = collect(self::families())->firstWhere('slug', $familySlug);

        if (! $family || empty($family['variants'])) {
            return ['emoji' => '🎯', 'tagline' => $mode->description ?? '', 'sort' => 99];
        }

        $normalizedName = self::normalize($mode->name);

        foreach ($family['variants'] as $variant) {
            foreach ($variant['keywords'] as $keyword) {
                if (str_contains($normalizedName, self::normalize($keyword))) {
                    return [
                        'emoji' => $variant['emoji'],
                        'tagline' => $variant['tagline'],
                        'sort' => $variant['sort'] ?? 99,
                    ];
                }
            }
        }

        return ['emoji' => '🎯', 'tagline' => $mode->description ?? '', 'sort' => 99];
    }

    /**
     * Normalize a string for comparison: lowercase, ASCII-only, alphanumerics.
     * "Blood Oaths!" -> "bloodoaths"
     * "King-Maker"   -> "kingmaker"
     */
    protected static function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($value));
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
                $normalizedName = self::normalize($mode->name);

                return collect($family['keywords'])
                    ->map(fn ($k) => self::normalize($k))
                    ->contains(fn ($keyword) => str_contains($normalizedName, $keyword));
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
