<?php

use App\Providers\AppServiceProvider;
use HiTaqnia\Haykal\Api\Identity\IdentityApiProvider;

return [
    AppServiceProvider::class,

    // Haykal API providers
    IdentityApiProvider::class,

    // ---------------------------------------------------------------
    // Application API providers (add one per module)
    // ---------------------------------------------------------------
    // App\Providers\Apis\ManagementApiProvider::class,
    // App\Providers\Apis\ResidentsApiProvider::class,

    // ---------------------------------------------------------------
    // Application panel providers (add one per Filament panel)
    // ---------------------------------------------------------------
    // App\Providers\Panels\AdminPanelProvider::class,
    // App\Providers\Panels\ManagementPanelProvider::class,
];
