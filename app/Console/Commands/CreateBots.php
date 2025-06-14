<?php

namespace App\Console\Commands;

use App\Events\UserCreated;
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
        for ($i = 0; $i < $this->argument('amount'); $i++) {
            UserCreated::fire(
                name: 'Bot '.$i,
                email: 'bot'.$i.'@bot.bot',
                encrypted_password: bcrypt('password'),
            );
        }
    }
}
