---
name: haykal-domain-actions
description: Use when writing or calling a domain Action class — the canonical place for business operations in HiTaqnia. Covers naming, location, signature (execute method, Result<T> return), DI via constructor promotion, when to use DB::transaction, dispatching domain events, and the boundary between Actions and FormRequest validation.
---

# haykal-domain-actions

Business operations live in Action classes — one class per operation, one public `execute(...)` method, returns `Result<T>`. They are the only place where multi-step writes, side-effects, and event dispatch happen. Controllers, Filament pages, and console commands compose Actions; Actions don't compose controllers.

## Location and naming

`domain/<Context>/Actions/<Noun>/<Verb><Noun>Action.php`

- `<Verb>` is imperative: `Create`, `Update`, `Delete`, `Cancel`, `Approve`, `ChangeStatus`, `Assign`, `Merge`, `Transfer`.
- `<Noun>` is the subject entity in singular: `Property`, `Task`, `Booking`.
- The folder lets one entity host many verbs without a wide flat directory.

Examples:
- `domain/PropertyManagement/Actions/Property/CreatePropertyAction.php`
- `domain/Tasks/Actions/Task/ChangeTaskStatusAction.php`
- `domain/AccessManagement/Actions/Vehicle/AssignParkingSlotAction.php`

## File header

```php
<?php

declare(strict_types=1);

namespace Domain\PropertyManagement\Actions\Property;

use Domain\PropertyManagement\Data\Property\CreatePropertyData;
use Domain\PropertyManagement\Models\Property;
use Domain\PropertyManagement\PropertyManagementErrors;
use HiTaqnia\Haykal\Core\ResultPattern\Result;
use Illuminate\Support\Facades\DB;
```

## Signature

```php
final class CreatePropertyAction
{
    public function __construct(
        private readonly PaymentServiceContract $paymentService,
    ) {}

    /**
     * @return Result<Property>
     */
    public function execute(CreatePropertyData $data): Result
    {
        // 1. Pre-checks against business invariants → Result::failure on violation
        if ($data->price !== null && $data->price < 0) {
            return Result::failure(PropertyManagementErrors::pricesCannotBeNegative());
        }

        // 2. Multi-step writes inside a transaction
        return DB::transaction(function () use ($data) {
            $property = Property::create([
                'complex_id' => $data->complexId,
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
                'price' => $data->price,
            ]);

            // 3. External side-effects — propagate failure if any
            $tenantResult = $this->paymentService->registerProperty($property);
            if ($tenantResult->isFailure()) {
                return $tenantResult;
            }

            // 4. Event dispatch after the row is persisted
            PropertyCreated::dispatch($property);

            // 5. Success
            return Result::success($property);
        });
    }
}
```

## Conventions

- **`final class`** — Actions are not designed for inheritance. Compose if you need to share logic.
- **One public method, named `execute`.** Static `execute()` is acceptable for stateless verbs that take no dependencies (e.g., `ChangeTaskStatusAction::execute(Task $task, TaskStatus $next)`); prefer instance + DI when there's anything to inject.
- **Input is a DTO** — `CreatePropertyData`, `UpdatePropertyData`. See **haykal-domain-data**. For trivial verbs taking primitives only (status change), accept the model and the new value directly.
- **Return `Result<T>`.** Type the generic in PHPDoc — `@return Result<Property>`. Don't return mixed shapes.
- **Constructor-promoted readonly DI** for service dependencies (`PaymentServiceContract`, `NotificationDispatcher`, etc.). Don't `app()->make` inside `execute`.
- **Wrap multi-step writes in `DB::transaction`** — including any subsequent reads that depend on those writes. Single-statement actions don't need a transaction.
- **Dispatch events post-write**, inside the transaction is fine (Laravel queues `afterCommit` listeners by default for queued listeners; for `ShouldDispatchAfterCommit` listeners this is automatic).
- **Don't catch exceptions to convert them to Result failures.** Exceptions are for unexpected problems and should propagate to the framework's exception handler. Use `Result::failure` only for *known business outcomes*. See **haykal-result-pattern**.
- **Don't authorize inside the Action.** Authorization is the controller / Filament page's job (`Gate::authorize`, policies). Actions assume the caller has the right.

## Static actions for trivial state changes

```php
final class ChangeTaskStatusAction
{
    public static function execute(Task $task, TaskStatus $next): Result
    {
        if (! self::isValidTransition($task->status, $next)) {
            return Result::failure(TasksErrors::invalidTaskStatusTransition());
        }

        if ($next === TaskStatus::Completed && $task->activeTimeLog) {
            return Result::failure(TasksErrors::taskCannotBeCompletedWithActiveTimeLog());
        }

        return DB::transaction(function () use ($task, $next) {
            $task->update(['status' => $next]);
            TaskStatusChanged::dispatch($task, $next);
            return Result::success($task);
        });
    }

    private static function isValidTransition(TaskStatus $from, TaskStatus $to): bool
    {
        // matrix or guard logic
    }
}
```

## Calling an Action

From a controller (see **haykal-api-module** for the full controller pattern):

```php
public function store(CreatePropertyRequest $request, CreatePropertyAction $action): JsonResponse
{
    $result = $action->execute(CreatePropertyData::from($request->validated()));

    if ($result->isFailure()) {
        return ApiResponse::businessError($result->getError());
    }

    return ApiResponse::created(data: PropertyResource::make($result->getData()));
}
```

From a Filament page:

```php
protected function handleRecordCreation(array $data): Property
{
    $result = app(CreatePropertyAction::class)
        ->execute(CreatePropertyData::from($data));

    if ($result->isFailure()) {
        Notification::make()
            ->danger()
            ->title($result->getError()->getMessage() ?? 'Failed to create property.')
            ->send();

        $this->halt();
    }

    return $result->getData();
}
```

## Don't

- Don't put HTTP concerns in Actions (no `JsonResponse`, no `request()`). They're transport-agnostic.
- Don't inject the request. Pass a DTO.
- Don't write Actions that span multiple bounded contexts as a single class. Coordinate via events / a thin "use case" service in `support/`.
- Don't return models when the operation produced a different aggregate (e.g., a created `Booking` plus a side `Notification`). Return the primary aggregate; the side-effects fire as events.
- Don't reuse one Action class for multiple verbs (`ManagePropertyAction` with a `$mode` flag is an anti-pattern). One verb, one class.

## References

- Result classes: `haykal-monorepo/packages/haykal-core/src/ResultPattern/{Result,Error}.php`
- Canonical examples in hibayt-backend: `domain/Core/Actions/Complex/CreateComplexAction.php`, `domain/Tasks/Actions/Task/ChangeTaskStatusAction.php`
- See also: **haykal-domain-data**, **haykal-result-pattern**, **haykal-api-module**
