<!--
Read /CLAUDE.md before opening a PR if you haven't.
Don't delete this template — fill it in. Sections that don't apply, mark N/A.
-->

## Summary

<!-- 1-3 sentences. What does this change and why? -->

## Scope of change

Pick one (delete the others):

- [ ] **New Challenge** — adds `app/Challenges/<Mode>/<Name>.php` and a test in `tests/Feature/Challenges/`
- [ ] **New Modifier** — adds `app/Modifiers/<Mode>/<Name>.php` and a test in `tests/Feature/Modifiers/`
- [ ] **Change to an existing Challenge or Modifier** — change is scoped to a single file under `app/Challenges/<Mode>/` or `app/Modifiers/<Mode>/`
- [ ] **New custom UI for a challenge/modifier** — adds a method on a per-mode `FormBuilderTraits/<Mode>` trait + a blade in `custom-form-elements/`
- [ ] **New game-mode-specific Verbs event** — adds to `app/Events/<Mode>/`
- [ ] **Bug fix scoped to one challenge/modifier**
- [ ] **Other** — explain below and tag @johnrudolph for design alignment before reviewing

## Architectural compliance

- [ ] I read `/CLAUDE.md` and `docs/architectural-patterns.md`.
- [ ] I followed an existing example in the same game mode (link or path it here): ___________
- [ ] I did not modify any trip-wire file (see `/CLAUDE.md`). If I did, I called it out below and explained why.
- [ ] All state changes go through Verbs events (no direct `$model->save()` / `Model::update()` in business logic).
- [ ] UI is built with the FormBuilder fluent API (`$this->form()->...->build()`), not raw arrays.
- [ ] No new frontend framework was introduced. Livewire + Alpine only.

## Tests

- [ ] I added a Pest feature test under `tests/Feature/` that exercises this change through Livewire.
- [ ] `php artisan test` passes locally.

## Trip-wire / out-of-scope changes (if any)

<!-- If your change touches generic infrastructure, top-level events, or files in /CLAUDE.md's trip-wire list, explain why no alternative worked. Otherwise: "None." -->

## Stale docs noticed

<!-- If anything in /CLAUDE.md, .ai-context.json, or docs/*.md contradicts current code, list it here so it can be updated. Otherwise: "None." -->
