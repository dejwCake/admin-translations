<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\Http\Requests\Admin\Translation\RescanTranslations;
use Brackets\AdminTranslations\Service\ScanAndSaveService;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;

final class RescanTranslationsController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function rescan(
        RescanTranslations $request,
        Config $config,
        Redirector $redirector,
        ScanAndSaveService $scanAndSaveService,
    ): array|RedirectResponse {
        $paths = (array) $config->get('admin-translations.scanned_directories', []);
        $scanAndSaveService->scanAndSave(new Collection($paths));

        if ($request->ajax()) {
            return [];
        }

        return $redirector->to('admin/translation');
    }
}
