<?php

namespace App\Console\Commands;

use App\Events\UserGainedMembership;
use App\Events\UserPromotedToSuperAdmin;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteToSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:promote-to-super-admin {email : The email address of the user to promote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote a user to super admin role by their email address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found');

            return;
        }

        UserGainedMembership::fire(user_id: $user->id);

        UserPromotedToSuperAdmin::fire(user_id: $user->id);
    }
}
