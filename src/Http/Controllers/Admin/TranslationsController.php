<?php

declare(strict_types=1);

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminListing\Services\AdminListingService;
use Brackets\AdminTranslations\Exceptions\WrongImportFile;
use Brackets\AdminTranslations\Exports\TranslationsExport;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\ExportTranslation;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\ImportTranslation;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\IndexTranslation;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Models\Translation;
use Brackets\AdminTranslations\Repositories\TranslationRepository;
use Brackets\AdminTranslations\Service\Import\TranslationService;
use Brackets\Translatable\Translatable;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class TranslationsController extends BaseController
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly TranslationRepository $translationRepository,
        private readonly Config $config,
        private readonly Redirector $redirector,
        private readonly ViewFactory $viewFactory,
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     * @throws Exception
     */
    public function index(IndexTranslation $request, Translatable $translatable): array|View
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListingService::create(Translation::class)->processRequestAndGet(
        // pass the request with params
            $request,
            // set columns to query
            ['id', 'namespace', 'group', 'key', 'text', 'created_at', 'updated_at'],
            // set columns to searchIn
            ['group', 'key', 'text->en', 'text->sk'],
            static function (Builder $query) use ($request): void {
                if ($request->has('group')) {
                    $query->whereGroup($request->group);
                }
            },
        );

        $locales = $translatable->getLocales();
        $userLocale = (isset($request->user()->language)
            && in_array($request->user()->language, $this->config->get('translatable.locales'), true))
            ? $request->user()->language
            : 'en';

        $collection = $data instanceof LengthAwarePaginator ? $data->getCollection() : $data;
        $collection->map(function (Translation $translation) use ($locales) {
            $locales->each(function (string $locale) use ($translation): void {
                $translation->setTranslation(
                    $locale,
                    $this->getCurrentTransForTranslation($translation, $locale),
                );
            });

            return $translation;
        });

        if ($request->ajax()) {
            return ['data' => $data, 'locales' => $locales];
        }

        return $this->viewFactory->make(
            'brackets/admin-translations::admin.translation.index',
            [
                'data' => $data,
                'locales' => $locales,
                'userLocale' => $userLocale,
                'groups' => $this->translationRepository->getUsedGroups(),
            ],
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTranslation $request, Translation $translation): array|RedirectResponse
    {
        $translation->update($request->validated());

        if ($request->ajax()) {
            return [];
        }

        return $this->redirector->to('admin/translation');
    }

    public function export(Excel $excel, ExportTranslation $request): BinaryFileResponse
    {
        $currentTime = CarbonImmutable::now()->toDateTimeString();
        $nameOfExportedFile = 'translations' . $currentTime . '.xlsx';

        return $excel->download(new TranslationsExport($request->getExportLanguages()), $nameOfExportedFile);
    }

    /**
     * @return array<string, int>|Collection<array<string, string|bool>>|JsonResponse
     */
    public function import(ImportTranslation $request): array|Collection|JsonResponse
    {
        if ($request->hasFile('fileImport')) {
            $chosenLanguage = $request->getChosenLanguage();

            try {
                $collectionFromImportedFile = $this->translationService->getCollectionFromImportedFile(
                    $request->file('fileImport'),
                    $chosenLanguage,
                );
            } catch (WrongImportFile $e) {
                return new JsonResponse($e->getMessage(), 422);
            }

            $existingTranslations = $this->translationService->getAllTranslationsForGivenLang($chosenLanguage);

            if ($request->getOnlyMissing()) {
                $filteredCollection = $this->translationService->getFilteredExistingTranslations(
                    $collectionFromImportedFile,
                    $existingTranslations,
                );
                $this->translationService->saveCollection($filteredCollection, $chosenLanguage);

                return [
                    'numberOfImportedTranslations' => count($filteredCollection),
                    'numberOfUpdatedTranslations' => 0,
                ];
            } else {
                $collectionWithConflicts = $this->translationService->getCollectionWithConflicts(
                    $collectionFromImportedFile,
                    $existingTranslations,
                    $chosenLanguage,
                );
                $numberOfConflicts = $this->translationService->getNumberOfConflicts($collectionWithConflicts);

                if ($numberOfConflicts === 0) {
                    return $this->translationService->checkAndUpdateTranslations(
                        $chosenLanguage,
                        $existingTranslations,
                        $collectionWithConflicts,
                    );
                }

                return $collectionWithConflicts;
            }
        }

        return new JsonResponse('No file imported', 422);
    }

    public function importResolvedConflicts(UpdateTranslation $request): array|JsonResponse
    {
        $resolvedConflicts = new Collection($request->getResolvedConflicts());
        $chosenLanguage = $request->getChosenLanguage();
        $existingTranslations = $this->translationService->getAllTranslationsForGivenLang($chosenLanguage);

        if (!$this->translationService->validImportFile($resolvedConflicts, $chosenLanguage)) {
            return new JsonResponse('Wrong syntax in your import', 409);
        }

        return $this->translationService->checkAndUpdateTranslations(
            $chosenLanguage,
            $existingTranslations,
            $resolvedConflicts,
        );
    }

    private function getCurrentTransForTranslation(Translation $translation, string $locale): array|string
    {
        if ($translation->group === '*') {
            return __($translation->key, [], $locale);
        }

        if ($translation->namespace === '*') {
            return trans($translation->group . '.' . $translation->key, [], $locale);
        }

        return trans($translation->namespace . '::' . $translation->group . '.' . $translation->key, [], $locale);
    }
}
