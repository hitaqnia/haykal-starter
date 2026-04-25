---
name: haykal-api-module
description: Use when adding or modifying an API module — controllers, FormRequests, JsonResources, route files, the ApiProvider subclass that registers the module with Scramble. Covers the response envelope (ApiResponse::ok|created|paginated|noContent|validationError|businessError), Spatie QueryBuilder usage for list endpoints, Scramble #[Group] attributes, and the routes/api.php → routes/api/<module>-api.php mounting convention.
---

# haykal-api-module

A Haykal app composes one or more independent API modules (identity, residents, management, …). Each lives under its own URL prefix, registers its own Scramble OpenAPI document, and follows the same envelope shape. This skill is the recipe for adding a new module end-to-end.

## Files in a module

```
app/Providers/Apis/<Module>ApiProvider.php             registration + Scramble docs
app/Apis/<Module>/
├── Controllers/<Group>/<Entity>Controller.php
├── Requests/<Group>/<Verb><Entity>Request.php
├── Resources/<Group>/<Entity>Resource.php
└── Middlewares/Resolve<Module>ApiContext.php          (when the module needs request context — tenant, profile)
routes/api/<module>-api.php                             route definitions
lang/<locale>/apis/<module>/requests/<verb>-<entity>.php attribute names + custom messages
```

## ApiProvider subclass

```php
<?php

declare(strict_types=1);

namespace App\Providers\Apis;

use Dedoc\Scramble\Support\Generator\SecurityScheme;
use HiTaqnia\Haykal\Api\ApiProvider;

final class ManagementApiProvider extends ApiProvider
{
    protected function name(): string { return 'management-api'; }

    protected function title(): string { return 'Management API'; }

    protected function description(): string
    {
        return 'Endpoints for staff and managers to operate complexes, units, and contracts.';
    }

    protected function path(): ?string
    {
        return 'api/management/{any}';
    }

    /**
     * Versioned alternative — use instead of path() for v1/v2 layouts.
     *
     * protected function versions(): array
     * {
     *     return [
     *         'v1' => 'api/management/v1/{any}',
     *         'v2' => 'api/management/v2/{any}',
     *     ];
     * }
     */

    protected function additionalSecuritySchemes(): array
    {
        return [
            'complex' => SecurityScheme::apiKey('header', 'X-Complex-Id'),
        ];
    }
}
```

Register in `bootstrap/providers.php` alongside the panel providers:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Apis\IdentityApiProvider::class,
    App\Providers\Apis\ManagementApiProvider::class,
];
```

The Huwiya bearer scheme is added automatically. `additionalSecuritySchemes()` is for tenant / profile headers.

## Route file mounting

`routes/api.php` (top-level):

```php
use Illuminate\Support\Facades\Route;

Route::prefix('management')
    ->middleware(['haykal.permissions.team'])
    ->group(base_path('routes/api/management-api.php'));

Route::prefix('identity')
    ->group(base_path('routes/api/identity-api.php'));
```

`routes/api/management-api.php`:

```php
use App\Apis\Management\Controllers\Properties\PropertyController;
use App\Apis\Management\Middlewares\ResolveManagementApiContext;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:huwiya-api', ResolveManagementApiContext::class])->group(function () {
    Route::get('properties', [PropertyController::class, 'index']);
    Route::post('properties', [PropertyController::class, 'store']);
    Route::get('properties/{property}', [PropertyController::class, 'show']);
    Route::patch('properties/{property}', [PropertyController::class, 'update']);
    Route::delete('properties/{property}', [PropertyController::class, 'destroy']);
});
```

`ResolveManagementApiContext` reads the `X-Complex-Id` header, validates the user has access, and calls `Tenancy::setTenantId(...)`. See **haykal-tenancy**.

## Controller

```php
<?php

declare(strict_types=1);

namespace App\Apis\Management\Controllers\Properties;

use App\Apis\Management\Requests\Properties\ListPropertiesRequest;
use App\Apis\Management\Requests\Properties\StorePropertyRequest;
use App\Apis\Management\Resources\Properties\PropertyResource;
use Dedoc\Scramble\Attributes\Group;
use Domain\PropertyManagement\Actions\Property\CreatePropertyAction;
use Domain\PropertyManagement\Data\Property\CreatePropertyData;
use Domain\PropertyManagement\Models\Property;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Properties')]
final class PropertyController
{
    /**
     * List properties (filterable, paginated).
     */
    public function index(ListPropertiesRequest $request): JsonResponse
    {
        $properties = QueryBuilder::for(Property::class)
            ->allowedFilters([
                AllowedFilter::callback('q', fn ($q, $value) => $q->whereLike('name', "%{$value}%")),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts(['number', 'price', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated(data: PropertyResource::collection($properties));
    }

    public function store(StorePropertyRequest $request, CreatePropertyAction $action): JsonResponse
    {
        $result = $action->execute(CreatePropertyData::from($request->validated()));

        if ($result->isFailure()) {
            return ApiResponse::businessError($result->getError());
        }

        return ApiResponse::created(data: PropertyResource::make($result->getData()));
    }

    public function show(Property $property): JsonResponse
    {
        return ApiResponse::ok(data: PropertyResource::make($property));
    }

    public function destroy(Property $property): JsonResponse
    {
        $property->delete();

        return ApiResponse::noContent();
    }
}
```

Conventions:
- **`#[Group('Name')]`** Scramble attribute on the class — drives the OpenAPI tag.
- **Final, no `extends Controller`** — HiTaqnia controllers are plain invokable classes; route binding still works. Use `extends Controller` only if you need middleware-via-constructor (rare).
- **One controller per entity / verb cluster.** Don't pile unrelated endpoints into a `MiscController`.
- **Always return through `ApiResponse::*`** — never `response()->json([...])` or `return $resource`.
- **Spatie QueryBuilder** for list endpoints with filtering / sorting — much cleaner than hand-rolled `where`s.
- **Hand all writes to a domain Action.** The controller's job is request → DTO → Action → response. No business logic.
- **Implicit binding (`Property $property`)** works because of ULID PKs + the global TenantScope. Bind by id in the route.

## FormRequest

```php
<?php

declare(strict_types=1);

namespace App\Apis\Management\Requests\Properties;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('properties.create');
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'array'],
            'name.en'     => ['required', 'string', 'max:255'],
            'name.ar'     => ['required', 'string', 'max:255'],
            'name.ku'     => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'price'       => ['nullable', 'integer', 'min:0'],
            'status'      => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return __('apis/management/requests/store-property.attributes');
    }
}
```

`attributes()` returns an array from the locale file:

```php
// lang/en/apis/management/requests/store-property.php
return [
    'attributes' => [
        'name'        => 'name',
        'name.en'     => 'English name',
        'name.ar'     => 'Arabic name',
        'name.ku'     => 'Kurdish name',
        'description' => 'description',
        'price'       => 'price',
        'status'      => 'status',
    ],
];
```

Mirror under `ar/`, `ku/`. See **haykal-localization**.

When validation fails, the framework's exception handler (registered by `HaykalApiServiceProvider`) emits the envelope at HTTP 422 — you don't have to call `validationError()` yourself.

## JsonResource

```php
<?php

declare(strict_types=1);

namespace App\Apis\Management\Resources\Properties;

use Domain\PropertyManagement\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Property
 */
class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,                     // @format ULID
            'number'      => $this->number,
            'name'        => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'status'      => $this->status->value,           // @var Domain\PropertyManagement\Enums\PropertyStatus
            'price'       => $this->price,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

- `@mixin <Model>` PHPDoc → IDE / static analysis sees the model's properties.
- Inline `@format` / `@var` comments → Scramble picks them up for the OpenAPI spec.
- For translatable columns, return all locales via `getTranslations(...)`.
- Use `->toIso8601String()` for timestamps.
- Wrap `$this->whenLoaded('relation')` for optional eager-loaded relations.

## Envelope (reminder)

```json
{
  "success": 1,
  "code": 200,
  "message": "OK",
  "data": { ... },
  "errors": null
}
```

Paginated `data` is shaped as `{ "items": [...], "pagination": { "page", "per_page", "total" } }` by `PaginatedResource`. Don't customize the envelope per endpoint.

## Don't

- Don't bypass `ApiResponse`. Returning a raw resource breaks the envelope.
- Don't put business validation in the FormRequest (price-must-be-positive-given-current-status). That's an Action's `Result::failure(...)` job.
- Don't use route closures. Always controller methods.
- Don't define routes globally — every route belongs to a module under `routes/api/<module>-api.php`.
- Don't write Sanctum-style auth. The guards are `huwiya-api` (stateless) and `huwiya-web` (Filament).
- Don't construct OpenAPI metadata by hand — Scramble reads request/resource shapes via reflection + the inline annotations above.

## References

- ApiProvider: `haykal-monorepo/packages/haykal-api/src/ApiProvider.php`
- ApiResponse: `haykal-monorepo/packages/haykal-api/src/Response/ApiResponse.php`
- Pagination wrapper: `haykal-monorepo/packages/haykal-api/src/Response/PaginatedResource.php`
- Exception → envelope translation: `haykal-monorepo/packages/haykal-api/src/HaykalApiServiceProvider.php`
- Starter README §"API" and §"Compose your own APIs"
- See also: **haykal-domain-actions**, **haykal-domain-data**, **haykal-result-pattern**, **haykal-localization**, **haykal-tenancy**
