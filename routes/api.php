<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Mount every API module here. Each module lives in its own file under
| `routes/api/` and belongs to a single API provider (subclass of
| `HiTaqnia\Haykal\Api\ApiProvider`) that handles its Scramble
| registration, security schemes, and docs UI.
|
*/

require __DIR__.'/api/identity-api.php';
