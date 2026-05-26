# Module Mapping

Where each game mode's code lives. Every mode follows the same pattern: a subfolder under `app/Challenges/`, `app/Modifiers/`, and `app/Events/`, plus optional support code. **Use this as a quick map; the directories themselves are the authoritative list.**

```
app/Challenges/<Mode>/             # challenges
app/Challenges/Support/<Mode>/     # per-mode support interfaces + traits (if needed)
app/Modifiers/<Mode>/              # modifiers
app/Events/<Mode>/                 # mode-specific Verbs events
database/seeders/<Mode>Seeder.php  # seed templates / game modes
resources/views/components/game-components/custom-form-elements/  # any custom UI blade components (no per-mode subfolders today; named by element type)
app/Support/FormBuilderTraits/<Mode>/  # per-mode FormBuilder methods (when custom UI is needed)
```

To list everything in a mode at any moment: `ls app/Challenges/<Mode>/ app/Modifiers/<Mode>/ app/Events/<Mode>/`.

## Current game modes

### PeckingOrder (`PeckingOrder`)

Covers PeckingOrder, BloodOath, and PyramidScheme variants. Individual play with upvote/downvote ballots and quiz mechanics.

- Challenges: `app/Challenges/PeckingOrder/`
- Modifiers: `app/Modifiers/PeckingOrder/` (BloodOaths, Alms, IndividualRecruiter, IndividualResignation)
- Events: `app/Events/PeckingOrder/`
- Shared logic: `app/Challenges/Support/PeckingOrder/` (`SupportsPeckingOrderBallots` + `HasPeckingOrderBallots`)
- Seeders: `database/seeders/PeckingOrderSeeder.php`, `BloodOathSeeder.php`, `PyramidSchemeSeeder.php`

### Laracon2025 (`Laracon2025`)

Team-based event game with cooperative and adversarial team mechanics.

- Challenges: `app/Challenges/Laracon2025/` (team-based)
- Modifiers: `app/Modifiers/Laracon2025/` (TeamSecretAlliance, TeamRecruiter, TeamSecretCodes, TeamResignation)
- Events: `app/Events/Laracon2025/`
- Shared logic: `app/Challenges/Support/Laracon2025/` (`SupportsTeamSwaps` + `HasTeamSwaps`, `HasTeamPairs`)
- Seeder: `database/seeders/Laracon2025Seeder.php`

### TierList (`TierList`)

Players construct and guess tier lists.

- Challenges: `app/Challenges/TierList/` (TierListConstructionPhase, TierListGuess)
- Modifiers: `app/Modifiers/TierList/` (TierListModifier)
- Events: `app/Events/TierList/`
- Custom UI: `app/Support/FormBuilderTraits/TierList/TierListFormElements.php` → `resources/views/components/game-components/custom-form-elements/tier-list-guess.blade.php`
- Seeder: `database/seeders/TierListSeeder.php`

### Farm (`Farm`)

Grid-based simulation with movement, harvesting, building, and team coordination.

- Challenges: `app/Challenges/Farm/`
- Modifiers: `app/Modifiers/Farm/` (FarmTeams, FarmActions, FarmSkills, FarmMap)
- Events: `app/Events/Farm/`
- Custom UI: `app/Support/FormBuilderTraits/Farm/FarmFormElements.php` → blade components for `farm-map`, `farm-actions`, and `farm-space-elements/{field,stash,trap}`
- Seeder: `database/seeders/FarmSeeder.php`

### ChooseSafetyOrDanger

Currently a placeholder directory under `app/Challenges/` — no concrete challenges yet.

## Core (game-agnostic, lives at the top of each tree)

These files do **not** belong to a single game mode. Touching them is a trip-wire (see `/CLAUDE.md`).

- `app/Challenges/BaseChallengeClass.php`, `app/Challenges/ChallengeRegistry.php`
- `app/Modifiers/BaseModifierClass.php`, `app/Modifiers/ModifierRegistry.php`
- `app/Challenges/IndividualFiller.php`, `app/Challenges/TeamFiller.php` (test utilities)
- `app/Livewire/GameDashboard.php`, `PlayerPage.php`, `TeamPage.php`, `SecretsPage.php`, `PreGameLobby.php`, `ModifierConfigurationPage.php`
- `app/Livewire/Concerns/HandlesClassActions.php`
- `app/Support/FormBuilder.php`, `app/Support/FrontendComponentProcessor.php`
- `app/States/*.php`
- `app/Events/*.php` (top-level events: game lifecycle, player lifecycle, user lifecycle; all mode-specific events go in `app/Events/<Mode>/`)
- `app/Events/Traits/*.php` (event traits: `HasGame`, `HasPlayer`, `HasChallenge`, etc.)
- `app/Models/*.php`
- `resources/views/components/game-components/form.blade.php`, `scoreboard.blade.php`, `countdown-timer.blade.php`
- `database/seeders/DatabaseSeeder.php`, `UserSeeder.php`

## Adding a new game mode

1. Create `app/Challenges/<NewMode>/`, `app/Modifiers/<NewMode>/`, `app/Events/<NewMode>/`.
2. If shared logic is needed across challenges in the mode, create `app/Challenges/Support/<NewMode>/`.
3. If custom UI is needed, add `app/Support/FormBuilderTraits/<NewMode>/<Name>FormElements.php` and the matching blade components under `resources/views/components/game-components/custom-form-elements/`.
4. Add `database/seeders/<NewMode>Seeder.php` and register it in `DatabaseSeeder` if it should run by default.
5. Each new challenge/modifier auto-registers via `ChallengeRegistry` / `ModifierRegistry` based on its directory location — no manual wiring.
