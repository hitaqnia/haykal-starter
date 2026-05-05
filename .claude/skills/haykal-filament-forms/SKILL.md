---
name: haykal-filament-forms
description: Use when building Filament forms — translatable inputs, location/polygon pickers, dependent fields, custom validation rules. Covers TranslatableTabs, the Mapbox component family (now with closure-driven center/zoom/height for reactive maps), live() + afterStateUpdated patterns, and Rule injection via ->rule(new <DomainRule>(...)).
---

# haykal-filament-forms

The Haykal Filament form library is small but opinionated. Reuse these components instead of rolling custom Livewire fields — they handle locale, validation, and state-path quirks the Filament defaults don't.

## TranslatableTabs — multi-language inputs

For any field that needs `en` / `ar` / `ku` content (model with `HasTranslations`), wrap the inputs in `TranslatableTabs`:

```php
use HiTaqnia\Haykal\Filament\Forms\TranslatableTabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

TranslatableTabs::make('translations')
    ->languages(['en', 'ar', 'ku'])
    ->primaryLanguage('en')
    ->requirePrimaryLanguageOnly()         // or ->requireAllLanguages() / ->requiredLanguages(['en','ar'])
    ->fields([
        TextInput::make('name')->required(),
        Textarea::make('description'),
    ]);
```

Behavior:

- Renders one tab per locale; tab titles come from `__('languages.{locale}')` (see **haykal-localization**).
- Clones each field once per locale, binding state paths `name.en`, `name.ar`, `name.ku`.
- Tab icon reflects state at a glance:
  - filled: `phosphor-check-circle-duotone`
  - required-but-empty: `phosphor-warning-circle-duotone`
  - optional-and-empty: `phosphor-circle-dashed-duotone`
- Required-validation policy controlled by `requirePrimaryLanguageOnly` (default), `requireAllLanguages`, or `requiredLanguages([...])`.

The model column itself must be `jsonb` and listed in `public array $translatable`. See **haykal-models** and **haykal-migrations**.

## Mapbox components

Geographic input/output for any model storing coordinates or polygons (Magellan-backed columns).

```php
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationViewer;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsDrawer;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsViewer;

// Form: pick a single point
MapboxLocationPicker::make('coordinates');

// Form: draw / edit polygons
MapboxPolygonsDrawer::make('boundary');

// Infolist: show a point read-only
MapboxLocationViewer::make('coordinates');

// Infolist: show polygons read-only
MapboxPolygonsViewer::make('boundary');
```

Configuration is per-app in `config/mapbox.php` (token, style, default center). The components lazy-load JS, so unused panels pay no cost. Arabic / Hebrew / Persian map labels render correctly out of the box — `mapbox-gl-rtl-text` is registered (lazy) on first map init.

Pair with `support/Domain/Concerns/HasLocation.php` / `HasPolygon.php` traits on the model when porting from the canonical hibayt-backend pattern.

### Reactive Mapbox configuration

`mapHeight`, `mapCenter`, `mapZoom`, and `navigationControl` accept Filament closures and re-evaluate every render. Pair with `live()` on a sibling field to drive the map from another component:

```php
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

Select::make('city')->options([...])->live();

MapboxLocationPicker::make('coordinates')
    ->mapCenter(fn (Get $get): array => match ($get('city')) {
        'baghdad' => [44.3661, 33.3152],
        'erbil'   => [44.0090, 36.1900],
        default   => [-74.5, 40.0],
    })
    ->mapZoom(fn (Get $get): int => $get('city') ? 12 : 9);
```

## Dependent fields — `live()` + `afterStateUpdated`

For fields whose visibility / options / values depend on other fields:

```php
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

Select::make('block_id')
    ->label(__($key.'.fields.block'))
    ->options(fn () => Block::pluck('name', 'id'))
    ->live()
    ->afterStateUpdated(fn (Set $set) => $set('unit_id', null))
    ->required();

Select::make('unit_id')
    ->label(__($key.'.fields.unit'))
    ->options(fn (Get $get) => Unit::query()
        ->where('block_id', $get('block_id'))
        ->pluck('name', 'id'))
    ->visible(fn (Get $get) => filled($get('block_id')))
    ->required();
```

Use `live()` for state to flow on every change; `live(onBlur: true)` for cheaper updates.

## Cross-field validation — domain Rule classes

Custom `ValidationRule` classes live under `domain/<Context>/Rules/<Name>Rule.php`. Inject in form via `->rule(new <Rule>(...))`:

```php
use Domain\PropertyManagement\Rules\PropertyNumberRule;

TextInput::make('number')
    ->required()
    ->rule(fn ($record) => new PropertyNumberRule(ignoreId: $record?->id));
```

Pass a closure when the rule depends on the current record (so edit pages can ignore the row's own id during uniqueness checks). See `domain/Identity/Rules/PhoneNumberRule.php` for an example.

## Common patterns

- **Hidden fields** for state plumbing: `Hidden::make('block_id')->dehydrated()`.
- **Conditional visibility**: `->visible(fn (Get $get) => $get('type') === 'A')`.
- **Disabled on edit**: `->disabledOn('edit')`.
- **Default from tenancy**: `->default(fn () => Tenancy::getTenantId())` — but most tenant-scoped models set this via `HasTenant`'s `creating` hook automatically; you rarely need this in the form.

## Don't

- Don't use raw `Tabs` for multi-language inputs — `TranslatableTabs` handles required-validation, state paths, and the icon legend already.
- Don't reach for OpenStreetMap / Leaflet. The standard is Mapbox via the Haykal components.
- Don't put validation logic inline (`->rules(['regex:/.../'])`) when a domain Rule class would express intent better. Custom domain rules belong in `domain/<Context>/Rules/`.
- Don't forget `->live()` when another field depends on this one — without it, dependents won't update.

## References

- Source: `haykal-monorepo/packages/haykal-filament/src/Forms/TranslatableTabs.php`
- Mapbox: `haykal-monorepo/packages/haykal-filament/src/Mapbox/Components/{MapboxLocationPicker,MapboxLocationViewer,MapboxPolygonsDrawer,MapboxPolygonsViewer}.php`
- Mapbox closure trait: `haykal-monorepo/packages/haykal-filament/src/Mapbox/Concerns/InteractsWithMapbox.php`
- See also: **haykal-localization**, **haykal-filament-resource**, **haykal-models**
