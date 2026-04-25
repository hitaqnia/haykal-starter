---
name: haykal-domain-enums
description: Use when adding a backed PHP enum used as a model cast or in Filament UI. Covers HasLabel + HasColor + HasIcon contracts, label translation keys, the standard color palette, int vs string backing, model casts, and the castFromArray helper for arrays-of-enums.
---

# haykal-domain-enums

Domain enums live under `domain/<Context>/Enums/<Name>.php`. Backed by `int` for status-like / ordered values, by `string` for feature flags / lookup-style values. Always implement Filament's UI contracts so they render automatically in tables, badges, selects, and filters.

## File header

```php
<?php

declare(strict_types=1);

namespace Domain\Tasks\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
```

## Status / ordered enum (int-backed)

```php
enum TaskStatus: int implements HasLabel, HasColor, HasIcon
{
    case Draft = 1;
    case Pending = 2;
    case InProgress = 3;
    case OnHold = 4;
    case Completed = 5;
    case Cancelled = 6;

    public function getLabel(): string|Htmlable|null
    {
        return __("domains/tasks/enums.task_status.{$this->name()}.label");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'primary',
            self::InProgress, self::OnHold => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'phosphor-pencil-simple-duotone',
            self::Pending => 'phosphor-clock-duotone',
            self::InProgress => 'phosphor-play-duotone',
            self::OnHold => 'phosphor-pause-duotone',
            self::Completed => 'phosphor-check-circle-duotone',
            self::Cancelled => 'phosphor-x-circle-duotone',
        };
    }

    private function name(): string
    {
        return Str::snake($this->name);
    }
}
```

`getLabel()` reads from `lang/<locale>/domains/tasks/enums.php` under `task_status.<case_snake>.label`. Add the case to all three locale files when introducing a new value.

## Lookup / feature enum (string-backed)

```php
enum Feature: string implements HasLabel
{
    case Wallet = 'wallet';
    case Subscriptions = 'subscriptions';
    case Facilities = 'facilities';
    case AccessManagement = 'access_management';

    public function getLabel(): string
    {
        return __('domains/core/enums.feature.' . $this->value . '.label');
    }

    /**
     * Cast a list of strings | self instances to a list of self.
     * Useful when hydrating from JSON columns.
     *
     * @param  array<int, string|self>  $values
     * @return array<int, self>
     */
    public static function castFromArray(array $values): array
    {
        return array_map(
            fn (string|self $value) => $value instanceof self ? $value : self::from($value),
            $values,
        );
    }
}
```

For string-backed enums, the `value` is what's stored in the DB. Snake_case values, lowercase.

## Color palette

Stick to Filament's semantic palette so badges/columns are consistent across panels:

| Color | Use |
|---|---|
| `primary` | active, ongoing, neutral-positive |
| `success` | completed, paid, succeeded |
| `warning` | on-hold, awaiting, partial |
| `danger` | cancelled, failed, rejected |
| `gray` | draft, archived, inert |
| `info` | informational only (rare) |

Avoid bespoke hex colors. If a status genuinely needs a fresh color, raise it with the team — one-off palettes degrade panel consistency.

## Icon set

HiTaqnia uses Phosphor (`phosphor-*-duotone`) and Tabler (`tabler-*`) icon packages. Phosphor duotone is the default. Names from the Phosphor docs map directly: `phosphor-check-circle-duotone`, `phosphor-warning-circle-duotone`, etc. The starter publishes `config/haykal-filament-icons.php` with common aliases.

## Casting on the model

```php
protected $casts = [
    'status' => TaskStatus::class,
    'priority' => TaskPriority::class,
];
```

Eloquent stores the int (or string) `value` and hydrates back to the enum on access.

## Casting array-of-enum columns

For JSON columns holding a list of enum values, use `castFromArray` in accessors / Spatie Data:

```php
public function getFeatures(): array
{
    return Feature::castFromArray($this->features);
}
```

## Filament integration

- **Select** — `Select::make('status')->options(TaskStatus::class)` reads `getLabel()`.
- **Badge column** — `TextColumn::make('status')->badge()` reads `getLabel()` + `getColor()` + `getIcon()`.
- **Filter** — `SelectFilter::make('status')->options(TaskStatus::class)`.
- **Infolist entry** — `TextEntry::make('status')->badge()`.

No additional plumbing is needed — implementing `HasLabel`/`HasColor`/`HasIcon` is enough.

## Translation file shape

`lang/en/domains/tasks/enums.php`:

```php
return [
    'task_status' => [
        'draft' =>        ['label' => 'Draft'],
        'pending' =>      ['label' => 'Pending'],
        'in_progress' =>  ['label' => 'In progress'],
        'on_hold' =>      ['label' => 'On hold'],
        'completed' =>    ['label' => 'Completed'],
        'cancelled' =>    ['label' => 'Cancelled'],
    ],
];
```

Mirror under `lang/ar/` and `lang/ku/`. See **haykal-localization**.

## Don't

- Don't return raw English from `getLabel()`. Always `__()`.
- Don't add a case without translations in all three locales.
- Don't use unbacked enums (`enum Foo {}`) — they can't be cast to DB columns.
- Don't reuse a value across cases of an int-backed enum, even if you delete the old case. Reserve values; never recycle.
- Don't put business logic on the enum (e.g., `canTransitionTo()`). Put state machines in Action classes or a `<Entity>StateMachine` class — keep enums pure.

## References

- Examples in hibayt-backend: `domain/Tasks/Enums/TaskStatus.php`, `domain/Core/Enums/Feature.php`
- Filament contracts: `vendor/filament/support/src/Contracts/{HasLabel,HasColor,HasIcon}.php`
- See also: **haykal-localization**, **haykal-models**
