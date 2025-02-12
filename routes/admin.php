<?php

declare(strict_types=1);

use Brackets\AdminTranslations\Http\Controllers\Admin\RescanTranslationsController;
use Brackets\AdminTranslations\Http\Controllers\Admin\TranslationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:' . config('admin-auth.defaults.guard')])
    ->prefix('/admin/translations')
    ->name('admin/translations/')
    ->group(static function (): void {
        Route::get('/', [TranslationsController::class, 'index']);
        Route::get('/export', [TranslationsController::class, 'export'])
            ->name('export');
        Route::post('/import', [TranslationsController::class, 'import'])
            ->name('import');
        Route::post('/import/conflicts', [TranslationsController::class, 'importResolvedConflicts'])
            ->name('import/conflicts');
        Route::post('/rescan', [RescanTranslationsController::class, 'rescan']);

        Route::post('/{translation}', [TranslationsController::class, 'update']);
    });
