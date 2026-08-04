# Not a source file

`.md` is not in `admin-translations.scanned_extensions`, so this file is skipped without
being read. The calls below are syntactically valid and would otherwise be collected,
which is what makes this a meaningful fixture rather than an inert one.

    {{ __('Markdown must not be scanned') }}
    {{ trans('markdown.must.not.be.scanned') }}
