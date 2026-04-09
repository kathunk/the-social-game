<?php

namespace App\Jobs;

use App\Services\KitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscribeUserToKit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}

    public function handle(KitService $kit): void
    {
        $kit->subscribe($this->email, $this->name);
    }
}
