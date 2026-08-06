<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping of old event FQCNs to new namespaced FQCNs.
     * Organized by game mode module.
     */
    protected array $renames = [
        // Farm Events
        'App\\Events\\PlayerAbandonedFarmTeam' => 'App\\Events\\Farm\\PlayerAbandonedFarmTeam',
        'App\\Events\\PlayerBankedGrainInFarm' => 'App\\Events\\Farm\\PlayerBankedGrainInFarm',
        'App\\Events\\PlayerBootedFromFarmTeam' => 'App\\Events\\Farm\\PlayerBootedFromFarmTeam',
        'App\\Events\\PlayerBuiltRoad' => 'App\\Events\\Farm\\PlayerBuiltRoad',
        'App\\Events\\PlayerBuiltSilo' => 'App\\Events\\Farm\\PlayerBuiltSilo',
        'App\\Events\\PlayerBuiltTrapInFarm' => 'App\\Events\\Farm\\PlayerBuiltTrapInFarm',
        'App\\Events\\PlayerBuiltWalls' => 'App\\Events\\Farm\\PlayerBuiltWalls',
        'App\\Events\\PlayerBuiltWatchtower' => 'App\\Events\\Farm\\PlayerBuiltWatchtower',
        'App\\Events\\PlayerBurnedField' => 'App\\Events\\Farm\\PlayerBurnedField',
        'App\\Events\\PlayerCanceledRequestToJoinFarmTeam' => 'App\\Events\\Farm\\PlayerCanceledRequestToJoinFarmTeam',
        'App\\Events\\PlayerCreatedStash' => 'App\\Events\\Farm\\PlayerCreatedStash',
        'App\\Events\\PlayerDepositedToSilo' => 'App\\Events\\Farm\\PlayerDepositedToSilo',
        'App\\Events\\PlayerDepositedToStash' => 'App\\Events\\Farm\\PlayerDepositedToStash',
        'App\\Events\\PlayerHarvestedField' => 'App\\Events\\Farm\\PlayerHarvestedField',
        'App\\Events\\PlayerJoinedFarmTeam' => 'App\\Events\\Farm\\PlayerJoinedFarmTeam',
        'App\\Events\\PlayerMovedInFarm' => 'App\\Events\\Farm\\PlayerMovedInFarm',
        'App\\Events\\PlayerPickpocketedOpponent' => 'App\\Events\\Farm\\PlayerPickpocketedOpponent',
        'App\\Events\\PlayerPlantedField' => 'App\\Events\\Farm\\PlayerPlantedField',
        'App\\Events\\PlayerPromotedToTeamLeaderInFarm' => 'App\\Events\\Farm\\PlayerPromotedToTeamLeaderInFarm',
        'App\\Events\\PlayerRequestedToJoinFarmTeam' => 'App\\Events\\Farm\\PlayerRequestedToJoinFarmTeam',
        'App\\Events\\PlayerResetSkillsInFarm' => 'App\\Events\\Farm\\PlayerResetSkillsInFarm',
        'App\\Events\\PlayerSeizedFarmProperty' => 'App\\Events\\Farm\\PlayerSeizedFarmProperty',
        'App\\Events\\PlayerSpawnedInFarm' => 'App\\Events\\Farm\\PlayerSpawnedInFarm',
        'App\\Events\\PlayerTookFromStash' => 'App\\Events\\Farm\\PlayerTookFromStash',
        'App\\Events\\PlayerUpgradedSilo' => 'App\\Events\\Farm\\PlayerUpgradedSilo',
        'App\\Events\\PlayerUpgradedSkillInFarm' => 'App\\Events\\Farm\\PlayerUpgradedSkillInFarm',
        'App\\Events\\PlayerWithdrewFromSilo' => 'App\\Events\\Farm\\PlayerWithdrewFromSilo',
        'App\\Events\\TeamLeaderAcceptedRequestToJoinFarmTeam' => 'App\\Events\\Farm\\TeamLeaderAcceptedRequestToJoinFarmTeam',
        'App\\Events\\TeamLeaderDeclinedRequestToJoinFarmTeam' => 'App\\Events\\Farm\\TeamLeaderDeclinedRequestToJoinFarmTeam',

        // TierList Events
        'App\\Events\\PlayerSubmittedTierListGuess' => 'App\\Events\\TierList\\PlayerSubmittedTierListGuess',
        'App\\Events\\TierListSubmitted' => 'App\\Events\\TierList\\TierListSubmitted',

        // Laracon2025 Events
        'App\\Events\\PlayerAssignedSecretAllyInTeamGame' => 'App\\Events\\Laracon2025\\PlayerAssignedSecretAllyInTeamGame',
        'App\\Events\\PlayerInputSecretCode' => 'App\\Events\\Laracon2025\\PlayerInputSecretCode',
        'App\\Events\\PlayerPassedPotato' => 'App\\Events\\Laracon2025\\PlayerPassedPotato',
        'App\\Events\\PlayerSubmittedNuclearStrike' => 'App\\Events\\Laracon2025\\PlayerSubmittedNuclearStrike',
        'App\\Events\\PlayerSubmittedPopulartiyContestVote' => 'App\\Events\\Laracon2025\\PlayerSubmittedPopulartiyContestVote',
        'App\\Events\\PlayerSubmittedPlayDirty' => 'App\\Events\\Laracon2025\\PlayerSubmittedPlayDirty',
        'App\\Events\\PlayerSubmittedStayOnMessage' => 'App\\Events\\Laracon2025\\PlayerSubmittedStayOnMessage',
        'App\\Events\\PlayerTripledOpponentsVote' => 'App\\Events\\Laracon2025\\PlayerTripledOpponentsVote',
        'App\\Events\\SecretCodesAddedToModifier' => 'App\\Events\\Laracon2025\\SecretCodesAddedToModifier',

        // PeckingOrder Events
        'App\\Events\\PlayerBecameInvisible' => 'App\\Events\\PeckingOrder\\PlayerBecameInvisible',
        'App\\Events\\PlayerBoughtImmunity' => 'App\\Events\\PeckingOrder\\PlayerBoughtImmunity',
        'App\\Events\\PlayerBoughtSecurity' => 'App\\Events\\PeckingOrder\\PlayerBoughtSecurity',
        'App\\Events\\PlayerChoseHopeOrFear' => 'App\\Events\\PeckingOrder\\PlayerChoseHopeOrFear',
        'App\\Events\\PlayerChosePointsOrHiddenPoints' => 'App\\Events\\PeckingOrder\\PlayerChosePointsOrHiddenPoints',
        'App\\Events\\PlayerChoseSafetyOrDanger' => 'App\\Events\\PeckingOrder\\PlayerChoseSafetyOrDanger',
        'App\\Events\\PlayerGaveReferralBonus' => 'App\\Events\\PeckingOrder\\PlayerGaveReferralBonus',
        'App\\Events\\PlayerGerrymanderedOpponent' => 'App\\Events\\PeckingOrder\\PlayerGerrymanderedOpponent',
        'App\\Events\\PlayerMadeOathOfSolitude' => 'App\\Events\\PeckingOrder\\PlayerMadeOathOfSolitude',
        'App\\Events\\PlayerOfferedBloodOath' => 'App\\Events\\PeckingOrder\\PlayerOfferedBloodOath',
        'App\\Events\\PlayerPlayedGrandstandGambit' => 'App\\Events\\PeckingOrder\\PlayerPlayedGrandstandGambit',
        'App\\Events\\PlayerResignedInIndividualGame' => 'App\\Events\\PeckingOrder\\PlayerResignedInIndividualGame',
        'App\\Events\\PlayerSpiedOpponents' => 'App\\Events\\PeckingOrder\\PlayerSpiedOpponents',
        'App\\Events\\PlayerStoleTheBacon' => 'App\\Events\\PeckingOrder\\PlayerStoleTheBacon',
        'App\\Events\\PlayerSubmittedPeckingOrderBallot' => 'App\\Events\\PeckingOrder\\PlayerSubmittedPeckingOrderBallot',
        'App\\Events\\PlayerSubmittedQuizGuess' => 'App\\Events\\PeckingOrder\\PlayerSubmittedQuizGuess',
    ];

    public function up(): void
    {
        $table = config('verbs.tables.events', 'verb_events');

        foreach ($this->renames as $old => $new) {
            DB::table($table)
                ->where('type', $old)
                ->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        $table = config('verbs.tables.events', 'verb_events');

        foreach ($this->renames as $old => $new) {
            DB::table($table)
                ->where('type', $new)
                ->update(['type' => $old]);
        }
    }
};
