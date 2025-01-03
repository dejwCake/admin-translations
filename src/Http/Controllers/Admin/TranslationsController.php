<?php

namespace Brackets\AdminTranslations\Http\Controllers\Admin;

use Brackets\AdminListing\AdminListing;
use Brackets\AdminTranslations\Exports\TranslationsExport;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\ImportTranslation;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\IndexTranslation;
use Brackets\AdminTranslations\Http\Requests\Admin\Translation\UpdateTranslation;
use Brackets\AdminTranslations\Http\Responses\TranslationsAdminListingResponse;
use Brackets\AdminTranslations\Service\Import\TranslationService;
use Brackets\AdminTranslations\Translation;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TranslationsController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct(private readonly TranslationService $translationService) {
    }

    /**
     * Display a listing of the resource.
     *
     * @throws Exception
     */
    public function index(IndexTranslation $request): Responsable|TranslationsAdminListingResponse
    {

        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Translation::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['id', 'namespace', 'group', 'key', 'text', 'created_at', 'updated_at'],

            // set columns to searchIn
            ['group', 'key', 'text->en', 'text->sk'],
            static function (Builder $query) use ($request) {
                if ($request->has('group')) {
                    $query->whereGroup($request->group);
                }
            }
        );

        return new TranslationsAdminListingResponse($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTranslation $request, Translation $translation): array|Redirector|RedirectResponse
    {
        $translation->update($request->validated());

        if ($request->ajax()) {
            return [];
        }

        return redirect('admin/translation');
    }

    public function export(UpdateTranslation $request): BinaryFileResponse
    {
        $currentTime = Carbon::now()->toDateTimeString();
        $nameOfExportedFile = 'translations' . $currentTime . '.xlsx';
        return Excel::download(new TranslationsExport($request), $nameOfExportedFile);
    }

    /**
     * @return array<string, int>|Collection<array<string, string|bool>>|JsonResponse
     */
    public function import(ImportTranslation $request): array|Collection|JsonResponse
    {
        if ($request->hasFile('fileImport')) {
            $chosenLanguage = $request->getChosenLanguage();

            try {
                $collectionFromImportedFile = $this->translationService->getCollectionFromImportedFile($request->file('fileImport'), $chosenLanguage);
            } catch (Exception $e) {
                return response()->json($e->getMessage(), 409);
            }

            $existingTranslations = $this->translationService->getAllTranslationsForGivenLang($chosenLanguage);

            if ($request->input('onlyMissing') === 'true') {
                $filteredCollection = $this->translationService->getFilteredExistingTranslations($collectionFromImportedFile, $existingTranslations);
                $this->translationService->saveCollection($filteredCollection, $chosenLanguage);

                return ['numberOfImportedTranslations' => count($filteredCollection), 'numberOfUpdatedTranslations' => 0];
            } else {
                $collectionWithConflicts = $this->translationService->getCollectionWithConflicts($collectionFromImportedFile, $existingTranslations, $chosenLanguage);
                $numberOfConflicts = $this->translationService->getNumberOfConflicts($collectionWithConflicts);

                if ($numberOfConflicts === 0) {
                    return $this->translationService->checkAndUpdateTranslations($chosenLanguage, $existingTranslations, $collectionWithConflicts);
                }

                return $collectionWithConflicts;
            }
        }

        return response()->json('No file imported', 409);
    }

    public function importResolvedConflicts(UpdateTranslation $request): JsonResponse|array
    {
        $resolvedConflicts = new Collection($request->getResolvedConflicts());
        $chosenLanguage = $request->getChosenLanguage();
        $existingTranslations = $this->translationService->getAllTranslationsForGivenLang($chosenLanguage);

        if (!$this->translationService->validImportFile($resolvedConflicts, $chosenLanguage)) {
            return response()->json('Wrong syntax in your import', 409);
        }

        return $this->translationService->checkAndUpdateTranslations($chosenLanguage, $existingTranslations, $resolvedConflicts);
    }
}
