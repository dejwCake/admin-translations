@extends('brackets/admin-ui::admin.layout.default')

@section('title', trans('brackets/admin-translations::admin.title'))

@section('body')

    <translation-listing
        :data="{{ $data->toJson() }}"
        :url="'{{ url('admin/translations') }}'"
        :locales="{{ json_encode($locales) }}"
        :user-locale="'{{ $userLocale }}'"
        :groups="{{ json_encode($groups) }}"
        :translations="{{ json_encode([
            'title' => trans('brackets/admin-translations::admin.index.title'),
            'rescan_btn' => trans('brackets/admin-translations::admin.btn.re_scan'),
            'import_btn' => trans('brackets/admin-translations::admin.btn.import'),
            'export_btn' => trans('brackets/admin-translations::admin.btn.export'),
            'all_groups' => trans('brackets/admin-translations::admin.index.all_groups'),
            'search_placeholder' => trans('brackets/admin-ui::admin.placeholder.search'),
            'search_btn' => trans('brackets/admin-ui::admin.btn.search'),
            'group' => trans('brackets/admin-translations::admin.fields.group'),
            'default' => trans('brackets/admin-translations::admin.fields.default'),
            'created_at_label' => trans('brackets/admin-translations::admin.fields.created_at'),
            'edit_btn' => trans('brackets/admin-ui::admin.btn.edit'),
            'pagination_previous' => trans('brackets/admin-ui::admin.pagination.previous'),
            'pagination_next' => trans('brackets/admin-ui::admin.pagination.next'),
            'pagination_overview' => trans('brackets/admin-ui::admin.pagination.overview'),
            'no_items' => trans('brackets/admin-translations::admin.index.no_items'),
            'try_changing_items' => trans('brackets/admin-translations::admin.index.try_changing_items'),
            'edit' => trans('brackets/admin-translations::admin.index.edit'),
            'default_text' => trans('brackets/admin-translations::admin.index.default_text'),
            'translation' => trans('brackets/admin-translations::admin.index.translation'),
            'translation_for' => trans('brackets/admin-translations::admin.index.translation_for_language'),
            'save' => trans('brackets/admin-ui::admin.btn.save'),
            'import_title' => trans('brackets/admin-translations::admin.import.title'),
            'import_notice' => trans('brackets/admin-translations::admin.import.notice'),
            'upload_file' => trans('brackets/admin-translations::admin.import.upload_file'),
            'choose_file' => trans('brackets/admin-translations::admin.import.choose_file'),
            'language_to_import' => trans('brackets/admin-translations::admin.import.language_to_import'),
            'select_language' => trans('brackets/admin-translations::admin.fields.select_language'),
            'do_not_override' => trans('brackets/admin-translations::admin.import.do_not_override'),
            'conflict_found' => trans('brackets/admin-translations::admin.import.conflict_notice_we_have_found'),
            'conflict_to_import' => trans('brackets/admin-translations::admin.import.conflict_notice_translations_to_be_imported'),
            'conflict_differ' => trans('brackets/admin-translations::admin.import.conflict_notice_differ'),
            'current_value' => trans('brackets/admin-translations::admin.fields.current_value'),
            'imported_value' => trans('brackets/admin-translations::admin.fields.imported_value'),
            'next' => trans('brackets/admin-ui::admin.pagination.next'),
            'successfully_imported' => trans('brackets/admin-translations::admin.import.successfully_notice'),
            'successfully_updated' => trans('brackets/admin-translations::admin.import.successfully_notice_update'),
            'export' => trans('brackets/admin-translations::admin.index.export'),
            'export_notice' => trans('brackets/admin-translations::admin.export.notice'),
            'language_to_export' => trans('brackets/admin-translations::admin.export.language_to_export'),
        ]) }}"
        v-cloak
    ></translation-listing>

@endsection
