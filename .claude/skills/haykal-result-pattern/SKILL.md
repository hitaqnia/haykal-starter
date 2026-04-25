---
name: haykal-result-pattern
description: Use when writing or calling code that can fail in expected, recoverable ways — domain Action classes, validation against business rules, anywhere the caller needs to distinguish "happy path" from "known business failure" without throwing. Covers Result<T>, Error::make, the per-domain error code ranges, and how API controllers translate failures via ApiResponse::businessError.
---

# haykal-result-pattern

Recoverable, expected failures use `Result<T>`. Truly unexpected conditions (DB unreachable, programmer error, missing config) still throw. This is the single most-cited convention in HiTaqnia code; do not deviate.

## Classes to use

```php
use HiTaqnia\Haykal\Core\ResultPattern\Result;   // packages/haykal-core/src/ResultPattern/Result.php
use HiTaqnia\Haykal\Core\ResultPattern\Error;    // packages/haykal-core/src/ResultPattern/Error.php
```

`Result` is final and generic: `Result::success($data)` returns `Result<TData>`; `Result::failure(Error $error)` returns `Result<TData>`. Inspect with `isSuccess() / isFailure()`, unwrap with `getData() / getError()`. `Error::make(int $code, ?string $message = null)` constructs an `Error`.

## Convention

- **Action classes** (`domain/<Context>/Actions/...`) always return `Result`. Type the generic via PHPDoc: `/** @return Result<Property> */`. Never throw for business outcomes.
- **Failure messages** come from `__()` — never hardcoded English. Look up via `__('errors/<context>.<code>.message')`.
- **Error codes are integers in per-domain ranges**. One file per domain — `domain/<Context>/<Context>Errors.php` — exposes every code as a static factory method. This concentrates the "list of things that can go wrong" in one searchable place per domain.
- **Generic / cross-cutting validation problems** (input shape, missing field) belong to FormRequest validation, not `Result`. Reserve `Result::failure` for true business invariants ("can't book this slot — already taken", "wallet balance too low").
- **API controllers** translate a failed Result with `return ApiResponse::businessError($result->getError());` — this maps the integer code into the envelope's `code` field. See **haykal-api-module**.

## Error code ranges

Reserve a 100-code block per domain. Pick the next free range when adding a new domain — keep the table updated:

| Range | Domain |
|---:|---|
| 1000–1099 | Core (tenant/complex, settings, features) |
| 1100–1199 | Identity (auth, users, OTP) |
| 1200–1299 | PropertyManagement |
| 1300–1399 | SalesAgreements |
| 1400–1499 | Tasks |
| 1500–1599 | Ticketing |
| 1600–1699 | AccessManagement |
| 1700–1799 | Services |
| 1800–1899 | Facilities |
| 1900–1999 | Wallets |
| 2000–2099 | Employees |
| 2100–2199 | Content |
| 2200–2299 | Documentation |

Codes ≥ 1000 are recognized as business errors and surface as HTTP 409 Conflict in the envelope (`ApiResponse::businessError`).

## `<Context>Errors.php` shape

```php
<?php

declare(strict_types=1);

namespace Domain\Tasks;

use HiTaqnia\Haykal\Core\ResultPattern\Error;

final class TasksErrors
{
    public static function taskCannotBeStarted(): Error
    {
        return Error::make(1400, __('errors/tasks.1400.message'));
    }

    public static function taskAlreadyHasActiveTimeLog(): Error
    {
        return Error::make(1401, __('errors/tasks.1401.message'));
    }

    public static function taskCannotBeCompletedWithActiveTimeLog(): Error
    {
        return Error::make(1402, __('errors/tasks.1402.message'));
    }
}
```

Matching translation file `lang/en/errors/tasks.php`:

```php
<?php

return [
    1400 => ['message' => 'Task cannot be started in its current state.'],
    1401 => ['message' => 'Task already has an active time log.'],
    1402 => ['message' => 'Task cannot be completed while a time log is active.'],
];
```

Same file under `lang/ar/` and `lang/ku/`.

## Action usage

```php
public function execute(StartTaskData $data): Result
{
    $task = Task::findOrFail($data->taskId);

    if ($task->status !== TaskStatus::Pending) {
        return Result::failure(TasksErrors::taskCannotBeStarted());
    }

    return DB::transaction(function () use ($task) {
        $task->update(['status' => TaskStatus::InProgress, 'started_at' => now()]);

        TaskStarted::dispatch($task);

        return Result::success($task);
    });
}
```

## Controller usage

```php
public function start(StartTaskRequest $request, StartTaskAction $action): JsonResponse
{
    $result = $action->execute(StartTaskData::from($request->validated()));

    if ($result->isFailure()) {
        return ApiResponse::businessError($result->getError());
    }

    return ApiResponse::ok(data: TaskResource::make($result->getData()));
}
```

## When to throw instead

- **Validation that should produce HTTP 422** → FormRequest `rules()` or `ValidationRule`. Don't reach for `Result`.
- **Authorization** (user lacks permission) → `abort(403)` / Filament policy / `Gate::authorize()`. Don't model as a domain error.
- **Infrastructure** (DB connection, missing env, third-party 500) → throw. The Haykal API exception handler renders these as 5xx envelopes automatically.
- **Programmer error** (unreachable branch, broken invariant) → `throw new RuntimeException(...)`. Crash loud.

## References

- Source: `haykal-monorepo/packages/haykal-core/src/ResultPattern/{Result,Error}.php`
- Translation: `ApiResponse::businessError` at `haykal-monorepo/packages/haykal-api/src/Response/ApiResponse.php`
- Canonical examples in hibayt-backend: `domain/Tasks/TasksErrors.php`, `domain/Core/CoreErrors.php`
- See also: **haykal-domain-actions**, **haykal-api-module**, **haykal-localization**
