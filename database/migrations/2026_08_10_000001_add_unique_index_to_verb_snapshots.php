<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verbs' snapshot writer upserts by the snapshot row's own id, and its
 * (state_id, type) index is NOT unique — so two concurrent requests that
 * both reconstitute the same state (when no snapshot row exists yet) each
 * insert their own row. From then on every state load throws
 * MultipleRecordsFoundException ("2 records were found.") and every event
 * touching that state fails, wedging the game.
 *
 * Fix at the database level: enforce the one-row-per-state invariant that
 * SnapshotStore::loadOne already assumes. On MySQL, INSERT ... ON DUPLICATE
 * KEY UPDATE fires on ANY unique-key conflict, so with this index the
 * racing insert becomes an update of the winner's row — the race is fixed,
 * not just detected.
 *
 * Existing duplicates are deleted outright (all rows of a duplicated
 * group, not just the extras): snapshots are a derived cache, and states
 * reconstitute from the verb_events log, which is ground truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicated = DB::table('verb_snapshots')
            ->select('state_id', 'type')
            ->groupBy('state_id', 'type')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicated as $group) {
            DB::table('verb_snapshots')
                ->where('state_id', $group->state_id)
                ->where('type', $group->type)
                ->delete();
        }

        Schema::table('verb_snapshots', function (Blueprint $table) {
            $table->unique(['state_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('verb_snapshots', function (Blueprint $table) {
            $table->dropUnique(['state_id', 'type']);
        });
    }
};
