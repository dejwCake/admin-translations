<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminTranslations\Http\Requests\Admin\Translation\RescanTranslations;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Artisan;

class RescanTranslationsController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Display a listing of the resource.
     */
    public function rescan(RescanTranslations $request): array|Redirector|RedirectResponse
    {
        Artisan::call('admin-translations:scan-and-save');

        if ($request->ajax()) {
            return [];
        }

        return redirect('admin/translation');
    }
}
