<?php

declare(strict_types=1);

namespace Domain\Identity\Models;

use Domain\Identity\Database\Factories\UserFactory;
use Domain\Identity\QueryBuilders\UserQueryBuilder;
use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
use Huwiya\InteractsWithHuwiya;
use Huwiya\TokenClaims;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * Application User model.
 *
 * Authentication is delegated entirely to Huwiya via the `InteractsWithHuwiya`
 * trait from the `hitaqnia/huwiya-laravel` SDK. There is no local password
 * column, no Sanctum tokens, no MFA. The `huwiya_id` column is populated on
 * first OAuth login, and the six profile columns (`name`, `phone`, `email`,
 * `locale`, `zoneinfo`, `theme`) are synced from the token claims on every
 * login via the `getHuwiyaCreateAttributes` / `getHuwiyaUpdateAttributes`
 * overrides below.
 *
 * This model carries the HiTaqnia team's default Huwiya policies:
 *
 *   - claim-sync map: six profile columns mirrored verbatim from claims;
 *   - conflict policy: left at the SDK default (throws `HuwiyaConflictException`);
 *   - auto-registration: left at the SDK default (accepts every valid JWT).
 *
 * Override any SDK hook here to change behavior per project (invite-only
 * registration, phone/email recycling, richer lookup query, etc.).
 *
 * Utility glue (`PhoneNumberCast`, `UserQueryBuilder`) is pulled from
 * `hitaqnia/haykal-core`.
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory;
    use HasRoles;
    use HasUlids;
    use InteractsWithHuwiya;
    use InteractsWithMedia;
    use Notifiable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'locale',
        'zoneinfo',
        'theme',
    ];

    /** @var list<string> */
    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'phone' => PhoneNumberCast::class,
        ];
    }

    public function newEloquentBuilder($query): UserQueryBuilder
    {
        return new UserQueryBuilder($query);
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    // -------------------------------------------------------------
    // Huwiya SDK hook overrides — team defaults.
    // -------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaCreateAttributes(TokenClaims $claims): array
    {
        return $this->attributesFromHuwiyaClaims($claims);
    }

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaUpdateAttributes(TokenClaims $claims): array
    {
        return $this->attributesFromHuwiyaClaims($claims);
    }

    /**
     * Columns synced from Huwiya claims. The same map runs on create and
     * on every re-login so the app's copy of the user stays in step with
     * the IdP.
     *
     * @return array<string, mixed>
     */
    protected function attributesFromHuwiyaClaims(TokenClaims $claims): array
    {
        return [
            'name' => $claims->name,
            'phone' => $claims->phone,
            'email' => $claims->email,
            'locale' => $claims->locale,
            'zoneinfo' => $claims->zoneinfo,
            'theme' => $claims->theme,
        ];
    }
}
