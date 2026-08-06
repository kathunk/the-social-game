# Onboarding: contributing to The Social Game without a dev background

Welcome. You don't need to know PHP, Laravel, or git mechanics to ship things here. You need a working laptop, an AI coding assistant, and patience for the first hour of setup. This doc takes you from zero to your first merged PR.

Read it top to bottom once before doing anything. Then start at step 1.

> **About terminals.** This doc has terminal commands in `monospace boxes`. They work the same on macOS and Windows, but the *terminal you run them in* differs:
>
> - **macOS:** the built-in **Terminal** app. Open it via ⌘+Space → type "Terminal" → enter.
> - **Windows:** install **Git for Windows** from https://gitforwindows.org. That gives you **Git Bash**, a terminal that behaves like macOS's so the commands below work identically (forward slashes, `~`, `cp`, etc.). Open it from the Start menu after install. Alternatively, **Windows Terminal** with PowerShell 7+ also works for everything in this doc.
>
> Whenever this doc says "open Terminal," use Terminal on Mac or Git Bash on Windows.

---

## Step 1 — Install the dev environment (one time, ~20 min)

We use [Laravel Herd](https://herd.laravel.com/) — it's a one-click installer that gives you PHP, MySQL, and a local web server without any terminal gymnastics. Herd has both **macOS** and **Windows** builds; download the right one for your machine.

1. Download Herd from https://herd.laravel.com/ and run the installer. The free version is fine for everything in this doc.
2. Open the Herd app and let it finish setting up (it will install PHP, MySQL, and the local web server).
3. Herd will create a default projects folder for you:
   - **macOS:** `~/Herd/`
   - **Windows:** `C:\Users\<your-username>\Herd\` (you can also write this as `~/Herd/` in Git Bash)

   You can change this in Herd's settings, but you don't need to.

**Sanity check:** open your terminal (Terminal on Mac, Git Bash on Windows — see the note at the top) and run:

```bash
php --version
```

You should see PHP 8.3 or higher. If not, ping John.

---

## Step 2 — Get the code (one time, ~10 min)

1. Make a GitHub account at https://github.com if you don't have one. Tell John your username; he'll add you as a collaborator on `kathunk/the-social-game` with Write access.
2. **Accept the invite** in your GitHub email or notifications.
3. Install **GitHub Desktop** from https://desktop.github.com (Mac and Windows both supported) if you want a clickable git interface. You can use the terminal instead — your choice. The rest of this doc shows both.

Now clone the repo:

**GitHub Desktop:** File → Clone repository → search "kathunk/the-social-game" → choose a folder inside your Herd projects folder (e.g. `~/Herd/` on Mac, `C:\Users\<you>\Herd\` on Windows) → Clone.

**Terminal:**
```bash
cd ~/Herd
git clone https://github.com/kathunk/the-social-game.git
cd the-social-game
```

---

## Step 3 — Set up the project (one time, ~10 min)

In your terminal (Terminal on Mac, Git Bash on Windows), navigate to the project folder if you're not already there:

```bash
cd ~/Herd/the-social-game
```

Then run these in order, one at a time. If any fails, stop and ask John.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

(If `cp` doesn't work because you're using PowerShell instead of Git Bash, use `copy .env.example .env` instead — same effect.)

Now create the local database. Herd ships with MySQL built in. Open Herd's app, find the "Services" or "MySQL" panel, and create a database called `the_social_game` (or whatever name you want). Then in your `.env` file (open it in any text editor — VS Code, Sublime, even Notepad on Windows or TextEdit on Mac works; the file is at the project root), set:

```
DB_DATABASE=the_social_game
DB_USERNAME=root
DB_PASSWORD=
```

(Herd's default MySQL has no password locally — that's fine for dev.)

Run the migrations and seed sample data:

```bash
php artisan migrate
php artisan db:seed
```

If that runs without errors, you're set up.

---

## Step 4 — Run the app (every time you work)

You'll need three terminal windows or tabs open in the project directory. On Mac, Terminal supports tabs (⌘+T) and so does iTerm2. On Windows, **Windows Terminal** supports tabs natively (Ctrl+T); Git Bash also opens new windows easily.

In each one, run one of:

```bash
php artisan serve
npm run dev
php artisan reverb:start
```

(Herd users may not need `php artisan serve` because Herd auto-serves projects in your Herd projects folder. If `http://the-social-game.test` works in your browser, you can skip that one.)

Open your browser and visit `http://the-social-game.test` (or whatever Herd showed you).

You should see the home page. Create an account, click around, start a game. If it works, you have a working dev environment.

---

## Step 5 — Set up your AI assistant

Pick one or both. Both will automatically read the architectural rules from `CLAUDE.md` (Claude Code) or `.cursor/rules/` (Cursor) when you work in this folder. **You do not need to copy-paste rules in.**

### Option A: Claude Code

1. Install from https://claude.com/claude-code (the install page covers both macOS and Windows).
2. Open your terminal (Terminal on Mac, Git Bash on Windows), navigate to the project: `cd ~/Herd/the-social-game`.
3. Run `claude` to start a session.

That's it. Claude Code will auto-read `CLAUDE.md` every conversation.

### Option B: Cursor

1. Install from https://cursor.com.
2. Open the project folder in Cursor (File → Open Folder).
3. Cursor will automatically apply `.cursor/rules/architecture.mdc` on every chat.

You can use both. Most people pick one and stick with it.

### Authenticate GitHub for your AI assistant (highly recommended)

Once your AI assistant is installed, also install the **GitHub CLI** from https://cli.github.com (Mac and Windows both supported), then run:

```bash
gh auth login
```

Follow the prompts to log in with your GitHub account. After this is done, your AI assistant can do your entire git workflow for you — create branches, commit, push, open PRs, even respond to review comments — without you typing any `git` commands yourself. You can just say things like "commit this and open a PR" and it will. The manual commands shown in Step 6 are there as reference for when something goes sideways, not as required steps.

---

## Step 6 — Your first PR (the workflow you'll repeat every time)

This is the loop you'll use for every change. Practice it once with a trivial edit (fix a typo, change a button label) before doing anything real.

> **You probably don't need to type any of these commands.** If you finished the "Authenticate GitHub for your AI assistant" step above, you can just say "create a branch called `fix/button-typo`, commit my changes, push it, and open a PR" and your assistant will handle every command below for you. The terminal commands here are reference material for when something goes sideways or you want to understand what's happening — not a script you have to run by hand.

### 6a. Make a new branch

Never work directly on `main`. Always create a branch first.

**GitHub Desktop:** Branch menu → New branch → name it something like `fix/button-typo`.

**Terminal:**
```bash
git checkout main
git pull
git checkout -b fix/button-typo
```

### 6b. Talk to your AI assistant

Tell it what you want to do. Some example prompts:

> "I want to add a new challenge to PeckingOrder where every player picks a color and the player who picked the rarest color gets a point. Walk me through the design first before writing any code. Read CLAUDE.md and the architectural patterns doc first."

Your assistant should:

1. Read `CLAUDE.md` and `docs/architectural-patterns.md`.
2. Find the closest existing example (probably `app/Challenges/PeckingOrder/IndividualBuddySystem.php`).
3. Ask you clarifying questions.
4. Propose a plan.

**If your assistant tries to:**
- Edit `GameDashboard.php`, `BaseChallengeClass.php`, `form.blade.php`, or any file in the "trip-wire" list in `CLAUDE.md`,
- Add a new frontend framework,
- Skip writing tests,
- Modify a file you didn't ask it to,

→ **Stop it.** Quote the relevant line of `CLAUDE.md` back to it and ask it to find a different approach. If it can't, that's a sign your idea touches deeper architecture and you should ping John before continuing.

### 6c. Write tests

Don't skip this. Tell your assistant: "Write a Pest feature test in `tests/Feature/Challenges/` that exercises this through Livewire, following the pattern in `IndividualBuddySystemTest.php`."

Run tests:

```bash
php artisan test
```

Don't ship a PR with failing or skipped tests.

### 6d. Commit and push

**GitHub Desktop:** type a summary, click "Commit to fix/button-typo", then "Push origin".

**Terminal:**
```bash
git add .
git status              # eyeball: do these files look right?
git commit -m "Fix typo on home page button"
git push -u origin fix/button-typo
```

### 6e. Open the PR

GitHub will show a banner saying "Compare & pull request" — click it. Fill in the PR template (it appears automatically). Be honest about the scope. If your change touches generic infrastructure, say so.

John will get notified. He'll review, request changes, or merge. **You cannot merge it yourself** — that's intentional, not a bug.

When he merges:

```bash
git checkout main
git pull
git branch -d fix/button-typo
```

(Or click "Delete branch" in GitHub Desktop.)

You just shipped.

---

## Things that will trip you up early

- **"Your branch is behind main."** Run `git checkout main && git pull && git checkout your-branch && git merge main`. Or ask your AI assistant — it'll do it for you.
- **Tests pass locally but the PR comment says they fail.** There is no CI yet, so this can only happen if someone broke `main`. Tell John.
- **The AI suggests editing a file you don't recognize.** Open `CLAUDE.md` and check whether it's on the trip-wire list. If it is, the AI is wrong. Tell it.
- **You see merge conflicts.** Don't panic. Ask your AI assistant to walk you through resolving them, or screen-share with John.
- **You accidentally committed your `.env` file.** Don't push. Run `git reset HEAD~1` to undo the commit, then add `.env` to `.gitignore` if it's not there. (It already is, but just in case.)

---

## How to ask for help

- **Stuck on setup:** ping John.
- **AI suggesting something that violates the rules:** quote `CLAUDE.md` at it and tell it to try again.
- **AI is going in circles:** start a fresh conversation and link the relevant docs at the top: "Read /CLAUDE.md and docs/architectural-patterns.md first."
- **You're not sure if your design fits the architecture:** open a draft PR or paste the design into a message to John before writing the code.

---

## The vibe

This repo has strong architectural opinions on purpose. They give you a lot of freedom *within* the pattern — entirely new game mechanics, custom UI, novel scoring systems — while keeping the generic plumbing untouched. The constraint is the thing that lets the system scale. When in doubt: imitate, don't invent. Welcome aboard.
