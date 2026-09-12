<?php

namespace App\Actions\Users;

use App\Contracts\UserLifecycle;
use App\DTOs\CreateUserData;
use App\Models\User;

final class RegisterUserAction
{
    public function __construct(private readonly UserLifecycle $lifecycle) {}

    public function execute(CreateUserData $data): User
    {
        return $this->lifecycle->register($data->toArray());
    }
}
