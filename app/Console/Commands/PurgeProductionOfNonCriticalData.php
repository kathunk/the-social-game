<?php

namespace App\Console\Commands;

use App\Events\GameModeAdded;
use App\Events\GameModeArchived;
use App\Events\GameModeUnarchived;
use App\Events\GameTemplateAdded;
use App\Events\GameTemplateArchived;
use App\Events\GameTemplateUnarchived;
use App\Events\UserCreated;
use App\Events\UserGainedMembership;
use App\Events\UserPromotedToSuperAdmin;
use App\Events\UserSubscribedToNewsletter;
use App\Events\UserUnsubscribedFromNewsletter;
use Illuminate\Console\Command;
use Thunk\Verbs\Models\VerbEvent;

class PurgeProductionOfNonCriticalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-production-of-non-critical-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge non-game Verb events from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $event_classes_to_keep = [
            GameModeAdded::class,
            GameModeArchived::class,
            GameModeUnarchived::class,
            GameTemplateAdded::class,
            GameTemplateArchived::class,
            GameTemplateUnarchived::class,
            UserCreated::class,
            UserGainedMembership::class,
            UserPromotedToSuperAdmin::class,
            UserSubscribedToNewsletter::class,
            UserUnsubscribedFromNewsletter::class,
        ];

        // Count how many events will be deleted
        $eventsToDelete = VerbEvent::query()
            ->whereNotIn('type', $event_classes_to_keep)
            ->count();

        if ($eventsToDelete === 0) {
            $this->info('No non-critical events found to delete.');

            return;
        }

        $this->warn("⚠️  WARNING: This will permanently delete {$eventsToDelete} VerbEvent records from the database.");
        $this->line('This action will delete all VerbEvent records EXCEPT for the following critical event types:');

        foreach ($event_classes_to_keep as $eventClass) {
            $this->line('  • '.class_basename($eventClass));
        }

        if (! $this->confirm('Are you absolutely sure you want to proceed with this destructive action?')) {
            $this->info('Operation cancelled.');

            return;
        }

        // Double confirmation for extra safety
        if (! $this->confirm('This action cannot be undone. Type "YES" to confirm deletion:')) {
            $this->info('Operation cancelled.');

            return;
        }

        $this->info("Deleting {$eventsToDelete} non-critical VerbEvent records...");

        $deletedCount = VerbEvent::query()
            ->whereNotIn('type', $event_classes_to_keep)
            ->delete();

        $this->info("Successfully deleted {$deletedCount} VerbEvent records.");
    }
}
