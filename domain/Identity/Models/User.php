<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Domain\Identity\Database\Factories\UserFactory;
use HiTaqnia\Haykal\Core\Identity\Models\User as HuwiyaUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Application User.
 *
 * Extends the Haykal User (which wires `Huwiya\InteractsWithHuwiya`,
 * Spatie `HasRoles`, Media Library, `HasUlids`, `SoftDeletes`, and the
 * phone / locale / zoneinfo / theme claim sync) so every HiTaqnia app
 * starts from the same auth baseline. Application-specific fillable
 * columns, relations, Huwiya hook overrides, and Filament `HasTenants`
 * / `canAccessPanel` implementations belong here.
 */
class User extends HuwiyaUser
{
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
