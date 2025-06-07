<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReplayEvents extends Command
{
    protected $signature = 'verbs:replay-selective {--events=* : Specific event types to replay} {--exclude=* : Event types to exclude from replay} {--from-id= : Start from specific event ID} {--to-id= : End at specific event ID} {--dry-run : Show what would be replayed without actually replaying}';

    protected $description = 'Selectively replay Verbs events with filtering options';

    public function handle()
    {
        $events = $this->option('events');
        $exclude = $this->option('exclude');
        $fromId = $this->option('from-id');
        $toId = $this->option('to-id');
        $dryRun = $this->option('dry-run');

        if (empty($events) && empty($exclude) && ! $fromId && ! $toId) {
            $this->error('You must specify at least one filtering option: --events, --exclude, --from-id, or --to-id');

            return 1;
        }

        // Build the query
        $query = DB::table('verb_events');

        if ($fromId) {
            $query->where('id', '>=', $fromId);
        }

        if ($toId) {
            $query->where('id', '<=', $toId);
        }

        if (! empty($events)) {
            $query->whereIn('type', $events);
        }

        if (! empty($exclude)) {
            $query->whereNotIn('type', $exclude);
        }

        $query->orderBy('id');

        $eventsToReplay = $query->get();

        if ($eventsToReplay->isEmpty()) {
            $this->info('No events found matching the criteria.');

            return 0;
        }

        $this->info("Found {$eventsToReplay->count()} events to replay:");

        // Show what will be replayed
        $eventTypes = $eventsToReplay->groupBy('type');
        foreach ($eventTypes as $type => $events) {
            $this->line("  - {$type}: {$events->count()} events");
        }

        if ($dryRun) {
            $this->info('Dry run mode - no events were actually replayed.');

            return 0;
        }

        if (! $this->confirm('Do you want to proceed with replaying these events?')) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $this->info('Starting selective event replay...');
        $bar = $this->output->createProgressBar($eventsToReplay->count());

        foreach ($eventsToReplay as $eventData) {
            try {
                // Reconstruct and replay the event
                $eventClass = $eventData->type;

                if (! class_exists($eventClass)) {
                    $this->error("Event class {$eventClass} not found, skipping...");
                    $bar->advance();

                    continue;
                }

                $event = unserialize($eventData->data);

                // Apply the event to its states
                if (method_exists($event, 'apply')) {
                    $event->apply();
                }

                // Handle the event (create/update database records)
                if (method_exists($event, 'handle')) {
                    $event->handle();
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to replay event {$eventData->id} ({$eventData->type}): ".$e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Selective event replay completed!');

        return 0;
    }
}
