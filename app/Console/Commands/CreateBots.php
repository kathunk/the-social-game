<?php

namespace App\Console\Commands;

use App\Events\UserCreated;
use App\Models\User;
use Illuminate\Console\Command;

class CreateBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-bots {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! is_numeric($this->argument('amount'))) {
            $this->error('Amount must be an integer');

            return;
        }

        if ($this->argument('amount') <= 0) {
            $this->error('Amount must be greater than 0');

            return;
        }

        $latest_bot = User::where('email', 'like', 'bot%@bot.bot')->get()->last() ?? null;

        $latest_bot_number = $latest_bot
            ? (int) explode(' ', $latest_bot?->name)[1]
            : 0;

        for ($i = $latest_bot_number + 1; $i < $latest_bot_number + 1 + $this->argument('amount'); $i++) {
            UserCreated::fire(
                name: 'Bot '.$i,
                email: 'bot'.$i.'@bot.bot',
                encrypted_password: bcrypt('password'),
            );
        }
    }
}
