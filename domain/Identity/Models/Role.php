<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use HiTaqnia\Haykal\Core\Identity\Models\Role as HaykalRole;

/**
 * Application Role.
 *
 * Extends the Haykal Role (ULID-keyed Spatie role) so the app can add
 * relations or attributes without forking the base class. Registered
 * in `config/permission.php` under `models.role`.
 */
class Role extends HaykalRole
{
}
