<?php

namespace App\Challenges\MorningRoutine\Rewards;

use App\Challenges\MorningRoutine\Rewards\Effects\AnarchistsCookbookEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\BossSuitEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\CoffeeEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\CompostBinEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\EnergyDrinkEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\EnforcersDonutEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\GamblersFallacyEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\HandSanitizerEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\HolyRobesEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\HousekeepingHandbookEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\IntermittentFastingEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\JanitorsUniformEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\JunkDrawerEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\LibrarianSweaterEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\LuckySocksEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\MirrorEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\MolassesEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\MonocleEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\OatmealEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\TrapDoorsForDummiesEffect;
use App\Challenges\MorningRoutine\Rewards\Effects\WhiteLinenSuitEffect;

class RewardRegistry
{
    public const ROOMS = ['bathroom', 'kitchen', 'laundry', 'study'];

    public const ROOM_HEADERS = [
        'bathroom' => 'Freshen up',
        'kitchen' => 'Fuel up',
        'laundry' => 'Dress for the job you want',
        'study' => 'Cram before class',
    ];

    /**
     * @return array<string, Reward>
     */
    protected static ?array $rewards = null;

    /**
     * @return array<string, Reward>
     */
    public static function all(): array
    {
        if (self::$rewards !== null) {
            return self::$rewards;
        }

        $rewards = [
            // BATHROOM
            new Reward(
                key: 'hand_sanitizer',
                room: 'bathroom',
                name: 'Hand sanitizer',
                flavor: 'Flatten the curve, and your opponents.',
                points: 1,
                mess: 1,
                effect_description: 'Immediately remove up to 5 mess from this room.',
                effect_class: HandSanitizerEffect::class,
            ),
            new Reward(
                key: 'hot_shave',
                room: 'bathroom',
                name: 'Hot shave',
                flavor: "Smooth as a baby's bottom.",
                points: 2,
                mess: 2,
            ),
            new Reward(
                key: 'luxurious_shower',
                room: 'bathroom',
                name: 'Luxurious Shower',
                flavor: 'Do not, my friends, become addicted to hot water. Because it all belongs to me.',
                points: 3,
                mess: 3,
            ),
            new Reward(
                key: 'mirror',
                room: 'bathroom',
                name: 'Mirror',
                flavor: "Mirror, mirror, on the wall, who's worth the most points?",
                points: 0,
                mess: 0,
                effect_description: 'At the end of the game, double the point value of your lowest value reward.',
                effect_class: MirrorEffect::class,
            ),
            new Reward(
                key: 'morning_constitutional',
                room: 'bathroom',
                name: 'Morning constitutional',
                flavor: 'Even had time to do the crossword!',
                points: 2,
                mess: 2,
            ),
            new Reward(
                key: 'tough_morning',
                room: 'bathroom',
                name: 'Tough morning',
                flavor: 'Everything ok in there?',
                points: 1,
                mess: 3,
            ),
            new Reward(
                key: 'unintentional_cold_plunge',
                room: 'bathroom',
                name: 'Unintentional cold plunge',
                flavor: "Who needs coffee when you've got this?",
                points: 1,
                mess: 1,
            ),

            // KITCHEN
            new Reward(
                key: 'coffee',
                room: 'kitchen',
                name: 'Coffee',
                flavor: 'The socially acceptable drug of choice.',
                points: 0,
                mess: 2,
                effect_description: 'You may take an additional reward from the study.',
                effect_class: CoffeeEffect::class,
            ),
            new Reward(
                key: 'compost_bin',
                room: 'kitchen',
                name: 'Compost Bin',
                flavor: "One man's egg shell is another man's worm food.",
                points: 0,
                mess: 3,
                effect_description: 'At the end of the game, your negative mess penalties count as positive.',
                effect_class: CompostBinEffect::class,
            ),
            new Reward(
                key: 'energy_drink',
                room: 'kitchen',
                name: 'Energy Drink',
                flavor: 'Here for a good time, not a long time.',
                points: 1,
                mess: 0,
                effect_description: 'After taking this, immediately clean this room.',
                effect_class: EnergyDrinkEffect::class,
            ),
            new Reward(
                key: 'enforcers_donut',
                room: 'kitchen',
                name: "Enforcer's donut",
                flavor: "He's a cop now?",
                points: 1,
                mess: 1,
                effect_description: 'The next time you bust an opponent leaving a room with a mess, double their penalty.',
                effect_class: EnforcersDonutEffect::class,
            ),
            new Reward(
                key: 'juice_cleanse',
                room: 'kitchen',
                name: 'Juice cleanse',
                flavor: 'Cleanliness begins from within.',
                points: 1,
                mess: 0,
            ),
            new Reward(
                key: 'junk_drawer',
                room: 'kitchen',
                name: 'Junk drawer',
                flavor: 'Sauce packets, dead batteries, and keys to nowhere.',
                points: 0,
                mess: 0,
                effect_description: 'Get a random kitchen reward not in this game.',
                effect_class: JunkDrawerEffect::class,
            ),
            new Reward(
                key: 'molasses',
                room: 'kitchen',
                name: 'Molasses',
                flavor: 'Careful what you wish for.',
                points: 1,
                mess: 3,
                effect_description: 'The next time an opponent busts you, they get covered in molasses: gain 2 mess penalty.',
                effect_class: MolassesEffect::class,
            ),
            new Reward(
                key: 'oatmeal',
                room: 'kitchen',
                name: 'Oatmeal',
                flavor: 'A healthy breakfast that runs right through you.',
                points: 1,
                mess: 3,
                effect_description: 'You may take an additional reward from the bathroom.',
                effect_class: OatmealEffect::class,
            ),

            // LAUNDRY
            new Reward(
                key: 'boss_suit',
                room: 'laundry',
                name: 'Boss suit',
                flavor: 'Show them who wears the pinstripes around here.',
                points: 1,
                mess: 2,
                effect_description: 'Ignore the next time an opponent busts you, and take no penalty for your mess.',
                effect_class: BossSuitEffect::class,
            ),
            new Reward(
                key: 'janitors_uniform',
                room: 'laundry',
                name: "Janitor's uniform",
                flavor: 'Clean up on aisle 6!',
                points: 0,
                mess: 3,
                effect_description: 'Every time you clean a room, gain a point.',
                effect_class: JanitorsUniformEffect::class,
            ),
            new Reward(
                key: 'holy_robes',
                room: 'laundry',
                name: 'Holy robes',
                flavor: 'Holier than thou, and thou, and thou.',
                points: 0,
                mess: 2,
                effect_description: 'The next time an opponent queues into your room and there is no mess, gain 3 points.',
                effect_class: HolyRobesEffect::class,
            ),
            new Reward(
                key: 'lucky_socks',
                room: 'laundry',
                name: 'Lucky socks',
                flavor: 'Luck is what happens when wealth meets nepotism.',
                points: 0,
                mess: 1,
                effect_description: 'The next time you enter the hallway, and there are opponents queued for other rooms but not your room, gain 3 points.',
                effect_class: LuckySocksEffect::class,
            ),
            new Reward(
                key: 'librarian_sweater',
                room: 'laundry',
                name: 'Librarian sweater',
                flavor: 'Cardigans never go out of style.',
                points: 0,
                mess: 1,
                effect_description: 'At the end of the game, if the study has 0 mess, you gain 3 points.',
                effect_class: LibrarianSweaterEffect::class,
            ),
            new Reward(
                key: 'monocle',
                room: 'laundry',
                name: 'Monocle',
                flavor: 'Second best thing to opera glasses.',
                points: 2,
                mess: 1,
                effect_description: 'When in the hallway, you can see the mess level of each room with an open door.',
                effect_class: MonocleEffect::class,
            ),
            new Reward(
                key: 'parachute_pants',
                room: 'laundry',
                name: 'Parachute pants',
                flavor: 'Looking great is its own reward.',
                points: 3,
                mess: 3,
            ),
            new Reward(
                key: 'white_linen_suit',
                room: 'laundry',
                name: 'White linen suit',
                flavor: 'Spotless, and soft to the touch. Quite dashing.',
                points: 0,
                mess: 3,
                effect_description: 'At the end of the game, if you have 0 mess penalties, gain 3 points.',
                effect_class: WhiteLinenSuitEffect::class,
            ),

            // STUDY
            new Reward(
                key: 'anarchists_cookbook',
                room: 'study',
                name: "Anarchist's Cookbook",
                flavor: 'Cooking up something chaotic.',
                points: 0,
                mess: 3,
                effect_description: 'At the end of the game, gain points equal to the mess level of the kitchen.',
                effect_class: AnarchistsCookbookEffect::class,
            ),
            new Reward(
                key: 'gamblers_fallacy',
                room: 'study',
                name: "Gambler's fallacy",
                flavor: "It's not a game of chance, it's a game of skill.",
                points: 0,
                mess: 2,
                effect_description: 'At the end of this game, this reward will either be worth 3 or -1.',
                effect_class: GamblersFallacyEffect::class,
            ),
            new Reward(
                key: 'housekeeping_handbook',
                room: 'study',
                name: 'Housekeeping Handbook',
                flavor: 'I want this place to shine like the top of the Chrysler Building!',
                points: 0,
                mess: 2,
                effect_description: 'At the end of the game, if the bathroom has 0 mess, gain 4 points.',
                effect_class: HousekeepingHandbookEffect::class,
            ),
            new Reward(
                key: 'trap_doors_for_dummies',
                room: 'study',
                name: 'Trap Doors For Dummies',
                flavor: 'Not just for theater kids anymore.',
                points: 0,
                mess: 1,
                effect_description: 'You may move between unoccupied rooms without entering the hallway.',
                effect_class: TrapDoorsForDummiesEffect::class,
            ),
            new Reward(
                key: 'intermittent_fasting',
                room: 'study',
                name: 'Intermittent Fasting',
                flavor: "It's not a real diet unless you tell everyone about it.",
                points: 1,
                mess: 2,
                effect_description: 'At the end of the game, if you have 0 Kitchen rewards, gain 3 points.',
                effect_class: IntermittentFastingEffect::class,
            ),
        ];

        return self::$rewards = collect($rewards)->keyBy('key')->all();
    }

    public static function find(string $key): ?Reward
    {
        return self::all()[$key] ?? null;
    }

    /**
     * @return array<string, Reward>
     */
    public static function forRoom(string $room): array
    {
        return collect(self::all())
            ->filter(fn (Reward $r) => $r->room === $room)
            ->all();
    }

    /**
     * Pick N random reward keys for a given room.
     *
     * @return array<string>
     */
    public static function randomKeysForRoom(string $room, int $count): array
    {
        $room_rewards = collect(self::forRoom($room))->keys()->shuffle();

        return $room_rewards->take($count)->values()->all();
    }
}
