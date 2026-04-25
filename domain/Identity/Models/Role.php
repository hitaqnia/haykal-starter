<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Application Role — ULID-keyed Spatie role.
 *
 * Spatie ships with int primary keys; this subclass swaps them for ULIDs
 * so the `users.role_id` pivot matches the app's ULID user key, and adds
 * the minimal fillable set the panel requires. Registered in
 * `config/permission.php` under `models.role`.
 *
 * Tenant scoping is provided by Spatie's own team support (enabled via
 * `permission.teams = true`); the `team_id` column maps to the app's
 * active tenant through Haykal's `haykal.permissions.team` middleware.
 */
class Role extends SpatieRole
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
