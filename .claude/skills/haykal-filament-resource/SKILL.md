---
name: haykal-filament-resource
description: Use when creating or editing a Filament resource — model CRUD inside a panel. Covers extending BaseResource, splitting Form / Table / Infolist into sibling files (never inline), Pages (List/Create/Edit/View) extending Haykal base pages, the translation file convention BaseResource auto-resolves, getRecordSubNavigation for related pages, and policy wiring.
---

# haykal-filament-resource

Filament resources in HiTaqnia split their schemas into separate files for readability and reuse — `<Resource>Form`, `<Resource>Table`, `<Resource>Infolist` (each with a single static `configure(...)` method). The resource itself is thin, just routing and metadata. UI labels come from a translation file at `lang/<locale>/panels/<panel-id>/resources/<plural-kebab>.php`, auto-resolved by `BaseResource`.

## Directory layout

```
app/Panels/Management/Resources/
└── Properties/
    ├── PropertyResource.php
    ├── Schemas/
    │   ├── PropertyForm.php
    │   ├── PropertyTable.php
    │   └── PropertyInfolist.php
    └── Pages/
        ├── ListProperties.php
        ├── CreateProperty.php
        ├── EditProperty.php
        └── ViewProperty.php
```

(Filament v5 uses the directory-per-resource layout. The `<Resource>` folder hosts everything related.)

## The resource class

```php
<?php

declare(strict_types=1);

namespace App\Panels\Management\Resources\Properties;

use App\Panels\Management\Resources\Properties\Pages\CreateProperty;
use App\Panels\Management\Resources\Properties\Pages\EditProperty;
use App\Panels\Management\Resources\Properties\Pages\ListProperties;
use App\Panels\Management\Resources\Properties\Pages\ViewProperty;
use App\Panels\Management\Resources\Properties\Schemas\PropertyForm;
use App\Panels\Management\Resources\Properties\Schemas\PropertyInfolist;
use App\Panels\Management\Resources\Properties\Schemas\PropertyTable;
use Domain\PropertyManagement\Models\Property;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use HiTaqnia\Haykal\Filament\Resources\BaseResource;

class PropertyResource extends BaseResource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'phosphor-buildings-duotone';

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertyTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'view' => ViewProperty::route('/{record}'),
            'edit' => ViewProperty::route('/{record}/edit'),
        ];
    }
}
```

The labels (`getModelLabel`, `getPluralModelLabel`, `getNavigationLabel`, `getNavigationGroup`, `getNavigationParentItem`) are inherited from `BaseResource` and read from the translation file — don't override them on the resource.

## Translation file (required)

`lang/en/panels/management/resources/properties.php` (also `ar/`, `ku/`):

```php
return [
    'model' => [
        'singular' => 'Property',
        'plural' => 'Properties',
    ],
    'navigation' => [
        'label' => 'Properties',
        'group' => 'Real Estate',         // optional
        'parent' => null,                 // optional, name another resource's nav label to nest
    ],
    'form' => [
        'sections' => [
            'information' => [
                'heading' => 'Information',
                'description' => 'Basic property details.',
            ],
        ],
        'fields' => [
            'number' => 'Number',
            'name' => 'Name',
            'description' => 'Description',
            'price' => 'Price',
        ],
    ],
    'table' => [
        'columns' => [
            'number' => 'Number',
            'name' => 'Name',
            'status' => 'Status',
            'price' => 'Price',
        ],
    ],
    'infolist' => [
        // mirror form sections / labels
    ],
];
```

The file name is the plural kebab of the resource class minus `Resource`. `PropertyResource` → `properties.php`. `UnitLayoutResource` → `unit-layouts.php`. See **haykal-localization**.

## Schema files

### Form

```php
<?php

declare(strict_types=1);

namespace App\Panels\Management\Resources\Properties\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use HiTaqnia\Haykal\Filament\Forms\TranslatableTabs;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        $key = 'panels/management/resources/properties.form';

        return $schema->components([
            Section::make(__($key.'.sections.information.heading'))
                ->description(__($key.'.sections.information.description'))
                ->schema([
                    TextInput::make('number')
                        ->label(__($key.'.fields.number'))
                        ->required(),

                    TranslatableTabs::make('translations')
                        ->languages(['en', 'ar', 'ku'])
                        ->primaryLanguage('en')
                        ->requirePrimaryLanguageOnly()
                        ->fields([
                            TextInput::make('name')
                                ->label(__($key.'.fields.name'))
                                ->required(),
                            Textarea::make('description')
                                ->label(__($key.'.fields.description')),
                        ]),

                    Select::make('status')
                        ->label(__($key.'.fields.status'))
                        ->options(PropertyStatus::class)
                        ->required(),

                    TextInput::make('price')
                        ->label(__($key.'.fields.price'))
                        ->numeric(),
                ]),
        ]);
    }
}
```

### Table

```php
class PropertyTable
{
    public static function configure(Table $table): Table
    {
        $key = 'panels/management/resources/properties.table';

        return $table
            ->columns([
                TextColumn::make('number')->label(__($key.'.columns.number'))->searchable(),
                TextColumn::make('name')->label(__($key.'.columns.name'))->searchable(),
                TextColumn::make('status')->label(__($key.'.columns.status'))->badge(),
                TextColumn::make('price')->label(__($key.'.columns.price'))->money('IQD'),
            ])
            ->filters([
                SelectFilter::make('status')->options(PropertyStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
```

### Infolist

Same shape as Form, using `Filament\Infolists\Components\*` entries.

## Pages

Extend the Haykal base pages — they centralize small UX defaults.

```php
namespace App\Panels\Management\Resources\Properties\Pages;

use App\Panels\Management\Resources\Properties\PropertyResource;
use HiTaqnia\Haykal\Filament\Resources\Pages\BaseListPage;

class ListProperties extends BaseListPage
{
    protected static string $resource = PropertyResource::class;
}
```

Equivalents: `BaseCreatePage`, `BaseEditPage`. For a custom view page or anything else, extend Filament's standard page classes directly — no Haykal base for those yet.

## Calling Actions from pages

When a page does more than the default Eloquent `create()`/`update()`, hand the work to a domain Action (see **haykal-domain-actions**):

```php
class CreateProperty extends BaseCreatePage
{
    protected static string $resource = PropertyResource::class;

    protected function handleRecordCreation(array $data): Property
    {
        $result = app(CreatePropertyAction::class)
            ->execute(CreatePropertyData::from($data));

        if ($result->isFailure()) {
            Notification::make()->danger()
                ->title($result->getError()->getMessage() ?? 'Failed to create property.')
                ->send();
            $this->halt();
        }

        return $result->getData();
    }
}
```

## Sub-navigation

For resources with related pages (residents of a unit, assignees of a task), expose them via:

```php
public static function getRecordSubNavigation(Page $page): array
{
    return $page->generateNavigationItems([
        ViewProperty::class,
        EditProperty::class,
        ManagePropertyResidents::class,
    ]);
}
```

`ManagePropertyResidents` is a custom page extending `Filament\Resources\Pages\Page` under the resource's `Pages/` folder.

## Policies

Authorization comes from a Laravel policy on the model. Filament auto-discovers `<Model>Policy`. Define one per tenant-scoped resource and register it in `AppServiceProvider`.

## Don't

- Don't inline form/table/infolist schemas in the resource class — it grows past 500 lines fast. Split.
- Don't override `getModelLabel`/`getPluralModelLabel`/etc. — they read from the translation file by convention.
- Don't hardcode form labels / section titles. Use `__()` against the resource's key prefix.
- Don't put business logic in the page's `mutateFormDataBeforeSave`. Hand it to an Action and pass the validated DTO.

## References

- BaseResource: `haykal-monorepo/packages/haykal-filament/src/Resources/BaseResource.php`
- Pages: `haykal-monorepo/packages/haykal-filament/src/Resources/Pages/{BaseListPage,BaseCreatePage,BaseEditPage}.php`
- See also: **haykal-localization**, **haykal-filament-forms**, **haykal-filament-panel**, **haykal-domain-actions**
