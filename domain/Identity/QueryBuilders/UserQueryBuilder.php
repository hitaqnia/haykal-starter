<?php

declare(strict_types=1);

namespace Domain\Identity\QueryBuilders;

use Domain\Identity\Models\User;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;

/**
 * Application-scoped Eloquent builder for the User model.
 *
 * Adds phone-number lookups that funnel every inbound shape through the
 * Haykal `PhoneNumber` value object so the stored column and the query
 * argument share one canonical form.
 *
 * @extends Builder<User>
 */
final class UserQueryBuilder extends Builder
{
    public function wherePhoneNumber(string $phone): self
    {
        return $this->where('phone', (new PhoneNumber($phone))->getInternational());
    }

    public function getByPhoneNumber(string $phone): ?User
    {
        return $this->wherePhoneNumber($phone)->first();
    }
}
