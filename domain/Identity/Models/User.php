<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Domain\Identity\Database\Factories\UserFactory;
use HiTaqnia\Haykal\Core\Identity\Models\User as HuwiyaUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class User extends HuwiyaUser
{
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
