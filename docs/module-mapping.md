# Module Mapping — Proposed

> Review this file and correct anything that's wrong. Items marked with `(?)` are guesses.
> Once confirmed, this becomes the migration plan.

---

## Farm

### Challenges
- [x] FarmRound

### Modifiers
- [x] FarmActions
- [x] FarmMap
- [x] FarmSkills
- [x] FarmTeams

### Events
- [x] PlayerAbandonedFarmTeam
- [x] PlayerBankedGrainInFarm
- [x] PlayerBootedFromFarmTeam
- [x] PlayerBuiltRoad
- [x] PlayerBuiltSilo
- [x] PlayerBuiltTrapInFarm
- [x] PlayerBuiltWalls
- [x] PlayerBuiltWatchtower
- [x] PlayerBurnedField
- [x] PlayerCanceledRequestToJoinFarmTeam
- [x] PlayerCreatedStash
- [x] PlayerDepositedToSilo
- [x] PlayerDepositedToStash
- [x] PlayerHarvestedField
- [x] PlayerJoinedFarmTeam
- [x] PlayerMovedInFarm
- [x] PlayerPlantedField
- [x] PlayerPromotedToTeamLeaderInFarm
- [x] PlayerRequestedToJoinFarmTeam
- [x] PlayerResetSkillsInFarm
- [x] PlayerSeizedFarmProperty
- [x] PlayerSpawnedInFarm
- [x] PlayerTookFromStash
- [x] PlayerUpgradedSilo
- [x] PlayerUpgradedSkillInFarm
- [x] PlayerPickpocketedOpponent
- [x] PlayerWithdrewFromSilo
- [x] TeamLeaderAcceptedRequestToJoinFarmTeam
- [x] TeamLeaderDeclinedRequestToJoinFarmTeam

### FormBuilder Traits
- [x] FarmFormElements

### Blade Components
- [x] custom-form-elements/farm-actions.blade.php
- [x] custom-form-elements/farm-map.blade.php
- [x] custom-form-elements/farm-space.blade.php
- [x] custom-form-elements/farm-space-elements/ (field, stash, trap)

### Seeders
- [x] FarmSeeder

---

## TierList

### Challenges
- [x] TierListConstructionPhase
- [x] TierListGuess

### Modifiers
- [x] TierListModifier

### Events
- [x] PlayerSubmittedTierListGuess
- [x] TierListSubmitted

### FormBuilder Traits
- [x] TierListFormElements

### Blade Components
- [x] custom-form-elements/guess-tiers.blade.php

### Seeders
- [x] TierListSeeder

---

## Laracon2025

### Challenges
- [x] FlattenTheCurve
- [x] StayOnMessage
- [x] TeamBounty
- [x] TeamBrinksmanship
- [x] TeamHotPotato
- [x] TeamPopularityContest
- [x] TeamPrisonersDilemma
- [x] TeamWarmUp
- [x] TheGreatRealignment

### Modifiers
- [x] TeamRecruiter
- [x] TeamResignation
- [x] TeamSecretAlliance
- [x] TeamSecretCodes

### Events
- [x] PlayerAssignedSecretAllyInTeamGame
- [x] PlayerInputSecretCode
- [x] PlayerPassedPotato
- [x] PlayerSubmittedNuclearStrike
- [x] PlayerSubmittedPopulartiyContestVote
- [x] PlayerSubmittedPlayDirty
- [x] PlayerSubmittedStayOnMessage
- [x] PlayerTripledOpponentsVote
- [x] SecretCodesAddedToModifier

### Challenge Support
- [x] Interfaces/SupportsTeamSwaps
- [x] Traits/HasTeamSwaps
- [x] Traits/HasTeamPairs

### Seeders
- [x] Laracon2025Seeder

---

## PeckingOrder

> Encompasses PeckingOrder, BloodOath, and PyramidScheme game variants.

### Challenges
- [x] IndividualBloodOathHunterQuiz
- [x] IndividualBuddySystem
- [x] IndividualChooseHopeOrFear
- [x] IndividualChoosePointsOrHidden
- [x] IndividualChooseSafetyOrDanger
- [x] IndividualDoubleTrouble
- [x] IndividualEquilibrium
- [x] IndividualFirstShallBeLast
- [x] IndividualGerrymander
- [x] IndividualGrandstandGambit
- [x] IndividualHighScoreQuiz
- [x] IndividualHighTrustEnvironment
- [x] IndividualLargestDecreaseQuiz (?)
- [x] IndividualLargestIncreaseQuiz (?)
- [x] IndividualLowScoreQuiz (?)
- [x] IndividualMostHiddenPointQuiz
- [x] IndividualMostTotalVotesQuiz (?)
- [x] IndividualNoScoreChangeQuiz (?)
- [x] IndividualOathQuiz
- [x] IndividualOathSpy
- [x] IndividualSpecificScoreQuiz (?)
- [x] IndividualSpy
- [x] IndividualStealTheBacon
- [x] PyramidScheme

### Modifiers
- [x] Alms
- [x] BloodOaths
- [x] IndividualRecruiter
- [x] IndividualResignation

### Events
- [x] PlayerBecameInvisible (?)
- [x] PlayerBoughtImmunity (?)
- [x] PlayerBoughtSecurity (?)
- [x] PlayerChoseHopeOrFear
- [x] PlayerChosePointsOrHiddenPoints
- [x] PlayerChoseSafetyOrDanger
- [x] PlayerGaveReferralBonus (?)
- [x] PlayerGerrymanderedOpponent
- [x] PlayerMadeOathOfSolitude
- [x] PlayerOfferedBloodOath
- [x] PlayerPlayedGrandstandGambit
- [x] PlayerResignedInIndividualGame
- [x] PlayerSpiedOpponents
- [x] PlayerStoleTheBacon
- [x] PlayerSubmittedPeckingOrderBallot
- [x] PlayerSubmittedQuizGuess

### Challenge Support
- [x] Interfaces/SupportsPeckingOrderBallots
- [x] Traits/HasPeckingOrderBallots

### Seeders
- [x] PeckingOrderSeeder
- [x] BloodOathSeeder
- [x] PyramidSchemeSeeder

---

## Core (stays at root — not moved)

### Base Classes
- BaseChallengeClass
- BaseModifierClass

### Test Utilities
- IndividualFiller (challenge)
- TeamFiller (challenge)

### Registries
- ChallengeRegistry
- ModifierRegistry

### Events — Game Lifecycle
- ChallengeCreated
- ChallengeEnded
- ChallengeStarted
- GameCanceled
- GameCreated
- GameEnded
- GameModeAdded
- GameModeArchived
- GameModeUnarchived
- GameStarted
- GameTemplateAdded
- GameTemplateArchived
- GameTemplateUnarchived
- GameUpdated
- GameUpdatedForReverb
- ModifierConfigurationCreated
- ModifierConfigurationDeleted
- ModifierCreated
- TeamCreated

### Events — Player Lifecycle
- PlayerAbandonedGame
- PlayerJoinedTeam
- PlayerReadiedUp
- PlayerRemovedFromGame
- PlayerResignedInTeamGame

### Events — User Lifecycle
- StripeWebhookRequested
- UserAdmittedToGame
- UserConnectedTelegram
- UserCreated
- UserDemotedFromGameAdmin
- UserDisconnectedTelegram
- UserGainedMembership
- UserLostMembership
- UserPromotedToGameAdmin
- UserPromotedToSuperAdmin
- UserRejectedFromGame
- UserRequestedToJoinGame
- UserSubscribedToNewsletter
- UserSubscribedToPush
- UserSwitchedCurrentGame
- UserUnsubscribedFromNewsletter
- UserUnsubscribedFromPush
- UserUpdatedNotificationPreferences

### Event Traits (all stay at root)
- HasActiveChallenge
- HasActiveGame
- HasActivePlayer
- HasChallenge
- HasGame
- HasGameApplication
- HasGameMode
- HasGameTemplate
- HasMembership
- HasModifier
- HasModifierConfiguration
- HasPlayer
- HasPlayerOnTeam
- HasTeam
- HasUser

### Seeders
- DatabaseSeeder
- UserSeeder

### FormBuilder
- FormBuilder.php (core class stays, but game-specific traits move out)
