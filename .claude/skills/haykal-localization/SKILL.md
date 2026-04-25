---
name: haykal-localization
description: Use when adding any user-facing string. Covers the lang/ directory layout, the three required locales (en, ar, ku), enum / panel / API / error key conventions, and the keys BaseResource and TranslatableTabs read by convention.
---

# haykal-localization

Every user-facing string ships in three locales: **English (en)**, **Arabic (ar)**, **Kurdish (ku)**. There is no English-only fallback for content; missing translations are bugs, not features. Translation files live under `lang/<locale>/...` and are accessed via `__()`.

## Directory layout

```
lang/
├── en/
│   ├── auth.php
│   ├── common.php
│   ├── validation.php
│   ├── languages.php                          (locale code → label, used by TranslatableTabs)
│   ├── domains/
│   │   └── <context>/
│   │       └── enums.php                       enum case labels
│   ├── panels/
│   │   └── <panel-id>/
│   │       └── resources/
│   │           └── <resource-kebab-plural>.php  BaseResource keys + form/table strings
│   ├── apis/
│   │   └── <module>/
│   │       └── requests/
│   │           └── <request>.php               FormRequest attribute names + custom messages
│   └── errors/
│       └── <context>.php                       business error messages keyed by code
├── ar/   (mirror of en/, all keys translated)
└── ku/   (mirror of en/, all keys translated)
```

When you add a key to `lang/en/...`, add it to `lang/ar/...` and `lang/ku/...` in the same commit. CI / review should reject one-locale PRs.

## Key naming conventions

### Enums — `lang/<locale>/domains/<context>/enums.php`

```php
return [
    'task_status' => [
        'draft' =>       ['label' => 'Draft'],
        'pending' =>     ['label' => 'Pending'],
        'in_progress' => ['label' => 'In progress'],
        'completed' =>   ['label' => 'Completed'],
        'cancelled' =>   ['label' => 'Cancelled'],
    ],
    'task_priority' => [
        'low' =>    ['label' => 'Low'],
        'medium' => ['label' => 'Medium'],
        'high' =>   ['label' => 'High'],
    ],
];
```

Enum file names = enum class snake_case (`TaskStatus` → `task_status`). Case names = PHP case in snake_case. The enum's `getLabel()` reads `__('domains/<context>/enums.<enum>.<case>.label')`. See **haykal-domain-enums**.

### Panel resources — `lang/<locale>/panels/<panel-id>/resources/<plural-kebab>.php`

`BaseResource` reads `model.singular`, `model.plural`, `navigation.label`, optional `navigation.group`, `navigation.parent`. Add form/table/infolist strings under the same file.

```php
return [
    'model' => [
        'singular' => 'Property',
        'plural' => 'Properties',
    ],
    'navigation' => [
        'label' => 'Properties',
        'group' => 'Real Estate',
    ],
    'form' => [
        'sections' => [
            'information' => ['heading' => 'Information', 'description' => 'Basic property details.'],
        ],
        'fields' => [
            'name' => 'Name',
            'description' => 'Description',
            'price' => 'Price',
        ],
    ],
    'table' => [
        'columns' => [
            'number' => 'Number',
            'status' => 'Status',
        ],
    ],
];
```

Resource file name = plural kebab (`PropertyResource` → `properties.php`, `UnitLayoutResource` → `unit-layouts.php`). See **haykal-filament-resource**.

### API requests — `lang/<locale>/apis/<module>/requests/<request>.php`

```php
return [
    'list_properties' => [
        'attributes' => [
            'filter[q]' => 'search query',
            'filter[status]' => 'status filter',
            'sort' => 'sort field',
            'page' => 'page number',
        ],
    ],
];
```

FormRequest's `attributes()` returns `__('apis/<module>/requests/<request>.list_properties.attributes')`. Custom validation messages: `__('apis/<module>/requests/<request>.list_properties.messages.<rule>')`.

### Errors — `lang/<locale>/errors/<context>.php`

Keyed by the integer error code:

```php
return [
    1400 => ['message' => 'Task cannot be started in its current state.'],
    1401 => ['message' => 'Task already has an active time log.'],
];
```

`<Context>Errors::name()` constructs `Error::make(1400, __('errors/<context>.1400.message'))`. See **haykal-result-pattern**.

### Languages display labels — `lang/<locale>/languages.php`

Used by `TranslatableTabs` to label each locale tab. Each locale file contains every locale label translated into that locale:

```php
// lang/en/languages.php
return ['en' => 'English', 'ar' => 'Arabic', 'ku' => 'Kurdish'];

// lang/ar/languages.php
return ['en' => 'الإنجليزية', 'ar' => 'العربية', 'ku' => 'الكردية'];

// lang/ku/languages.php
return ['en' => 'ئینگلیزی', 'ar' => 'عەرەبی', 'ku' => 'کوردی'];
```

## Content / model translations

For columns translated via Spatie translatable (`$translatable = ['name', 'description']`), translations live in the database (JSONB column), not in `lang/`. See **haykal-models** and **haykal-migrations**.

## Reading translations

- `__('key')` — current request locale
- `__('key', ['attr' => $value])` — interpolation
- `trans_choice('key', $count)` — pluralization (rare in HiTaqnia code; pluralization in ar/ku is non-trivial — prefer separate keys)

For Eloquent translatable columns:

```php
$property->name;                         // current locale
$property->getTranslation('name', 'ar'); // explicit locale
$property->setTranslation('name', 'en', 'New name');
```

## Validation messages

`lang/<locale>/validation.php` is published from Laravel + extended with HiTaqnia-specific keys for custom rules. When adding a custom `ValidationRule`, define its message key here and reference it in the rule:

```php
$fail(__('validation.unit_number_format'));
```

## Don't

- Don't hardcode user-facing English strings in PHP code or Blade. Always `__()`.
- Don't ship a key in `en/` without same key in `ar/` and `ku/`. Tests / lint should fail.
- Don't put translation strings in JS bundles unless going through the established `lang.json` pipeline (rare here).
- Don't use Laravel's JSON-only `lang/<locale>.json` files — HiTaqnia uses PHP files exclusively for namespacing.

## References

- BaseResource translation key resolver: `haykal-monorepo/packages/haykal-filament/src/Resources/BaseResource.php`
- TranslatableTabs language labels: `haykal-monorepo/packages/haykal-filament/src/Forms/TranslatableTabs.php` (uses `__('languages.{locale}')`)
- See also: **haykal-domain-enums**, **haykal-result-pattern**, **haykal-filament-resource**, **haykal-api-module**
