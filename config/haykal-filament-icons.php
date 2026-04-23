<?php

declare(strict_types=1);

use Filament\Actions\View\ActionsIconAlias;
use Filament\Forms\View\FormsIconAlias;
use Filament\Tables\View\TablesIconAlias;
use Filament\View\PanelsIconAlias;

/*
|--------------------------------------------------------------------------
| Haykal Filament Icon Overrides
|--------------------------------------------------------------------------
|
| Icon aliases used across every Haykal panel. Published to the application
| via `php artisan vendor:publish --tag=haykal-filament-icons` and loaded
| through the Filament icons config. Edit the published copy to rebrand
| on a per-application basis.
|
| The values are Blade-icon identifiers supplied by `codeat3/blade-phosphor-icons`
| (or `secondnetwork/blade-tabler-icons`). Install either package — or
| both — in the consuming application to ensure the identifiers resolve.
|
*/

return [

    // Panels
    PanelsIconAlias::USER_MENU_PROFILE_ITEM => 'phosphor-user-circle-duotone',

    // Actions
    ActionsIconAlias::VIEW_ACTION => 'phosphor-eye-duotone',
    ActionsIconAlias::EDIT_ACTION => 'phosphor-pencil-line-duotone',
    ActionsIconAlias::DELETE_ACTION => 'phosphor-trash-duotone',

    // Tables
    TablesIconAlias::ACTIONS_FILTER => 'phosphor-funnel-duotone',
    TablesIconAlias::ACTIONS_COLUMN_MANAGER => 'phosphor-columns-duotone',
    TablesIconAlias::HEADER_CELL_SORT_BUTTON => 'phosphor-arrows-down-up-light',
    TablesIconAlias::HEADER_CELL_SORT_ASC_BUTTON => 'phosphor-arrow-up-light',
    TablesIconAlias::HEADER_CELL_SORT_DESC_BUTTON => 'phosphor-arrow-down-light',
    TablesIconAlias::SEARCH_FIELD => 'phosphor-magnifying-glass-light',

    // Forms
    FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE => 'phosphor-trash-duotone',
    FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_CLONE => 'phosphor-copy-duotone',
    FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD => 'phosphor-eye-slash-duotone',
    FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD => 'phosphor-eye-duotone',
];
