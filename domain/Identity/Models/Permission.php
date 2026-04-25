<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Application Permission — ULID-keyed Spatie permission.
 *
 * Spatie ships with int primary keys; this subclass swaps them for ULIDs
 * to match the rest of the app's identifier scheme. Registered in
 * `config/permission.php` under `models.permission`.
 */
class Permission extends SpatiePermission
{
    use HasUlids;
}
