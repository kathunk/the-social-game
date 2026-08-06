# Branch Protection Setup

One-time setup for `main` on github.com/kathunk/the-social-game. This makes CODEOWNERS load-bearing — without protection, anyone with write access can merge straight to `main` and bypass review.

## What we want enforced

1. **No direct pushes to `main`.** All changes go through a PR.
2. **At least one approving review.** The CODEOWNERS file requires that approval to come from a listed owner.
3. **Code-owner review required** for files matched by CODEOWNERS (in our case, everything — `*` is mapped to `@johnrudolph`).
4. **Dismiss stale approvals on new commits.** If a contributor pushes new code after approval, the approval clears.
5. **No force-pushing to `main`.**
6. **No deleting `main`.**
7. **Require conversation resolution before merging.** Unresolved review comments block merge.

## Option A: GitHub UI

Repo → Settings → Branches → "Add branch ruleset" (or "Add classic branch protection rule"):

- **Branch name pattern:** `main`
- ✅ Require a pull request before merging
  - Required approvals: **1**
  - ✅ Dismiss stale pull request approvals when new commits are pushed
  - ✅ Require review from Code Owners
- ✅ Require conversation resolution before merging
- ❌ Allow force pushes (leave off)
- ❌ Allow deletions (leave off)
- ✅ (optional) Restrict who can push to matching branches → only `@johnrudolph`

## Option B: gh CLI (one command)

```bash
gh api -X PUT repos/kathunk/the-social-game/branches/main/protection \
  --input - <<'EOF'
{
  "required_status_checks": null,
  "enforce_admins": false,
  "required_pull_request_reviews": {
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": true,
    "required_approving_review_count": 1,
    "require_last_push_approval": false
  },
  "restrictions": null,
  "required_linear_history": false,
  "allow_force_pushes": false,
  "allow_deletions": false,
  "required_conversation_resolution": true,
  "lock_branch": false,
  "allow_fork_syncing": false
}
EOF
```

Verify with:

```bash
gh api repos/kathunk/the-social-game/branches/main/protection | jq '.required_pull_request_reviews'
```

## Notes

- `enforce_admins: false` means you (as admin) can still merge your own PRs without a second approval. If you ever want to require external approval for your own changes too, flip this to `true`.
- `required_status_checks` is `null` because there are no CI checks defined yet. If you later add a GitHub Actions workflow that runs `php artisan test`, add the check name to this list to gate merges on green tests.
- This is *classic* branch protection. GitHub also offers "rulesets" (newer, more flexible). Classic is simpler and sufficient for now.

## Adding your friend as a collaborator

1. Repo → Settings → Collaborators and teams → "Add people".
2. Add by GitHub username or email.
3. Set role to **Write**. (Not Maintain or Admin.)

With Write + the protection above, he can:

- ✅ Push to feature branches.
- ✅ Open PRs.
- ❌ Push to `main`.
- ❌ Merge a PR (only owners can, because CODEOWNERS requires your approval).
- ❌ Delete branches in the main repo's protected branches list.

He cannot accidentally ship code without your review.
