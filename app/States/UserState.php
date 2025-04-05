<?php

namespace App\States;

use Thunk\Verbs\State;

class UserState extends State
{
    public string $name;

    public string $email;

    public string $encrypted_password;

    public string $status;

    public bool $is_admin;
}
