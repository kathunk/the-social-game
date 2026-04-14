<?php

namespace App\Jobs;

use App\Services\CatacombianNewsletterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscribeUserToNewsletter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}

    public function handle(CatacombianNewsletterService $newsletter): void
    {
        $newsletter->subscribe($this->email, $this->name);
    }
}
