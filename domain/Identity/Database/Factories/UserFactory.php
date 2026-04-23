<?php

declare(strict_types=1);

namespace Domain\Identity\Database\Factories;

use Domain\Identity\Models\User;
use HiTaqnia\Haykal\Core\Database\Factories\UserFactory as HaykalUserFactory;

/**
 * Application User factory.
 *
 * Reuses every Haykal default (Huwiya id, Iraqi phone, locale /
 * zoneinfo / theme samples) but produces instances of the app's
 * `Domain\Identity\Models\User` subclass instead of the Haykal base.
 * Override states and add new ones here as the User model grows.
 *
 * @extends HaykalUserFactory<User>
 */
class UserFactory extends HaykalUserFactory
{
    protected $model = User::class;
}
