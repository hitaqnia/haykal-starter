<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use HiTaqnia\Haykal\Core\Identity\Models\Permission as HaykalPermission;

/**
 * Application Permission.
 *
 * Extends the Haykal Permission (ULID-keyed Spatie permission) so the
 * app can add relations or attributes without forking the base class.
 * Registered in `config/permission.php` under `models.permission`.
 */
class Permission extends HaykalPermission
{
}
