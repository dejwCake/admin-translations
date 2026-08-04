PHP and Blade call shapes. Every string below is either listed in
TranslationsScannerTest::positiveCases() or in negativeCases() -- nothing here is incidental.

=== Collected: replacement parameters ===

{{ __('php.args.single', ['name' => $name]) }}
{{ __("php.args.double", ['name' => $name]) }}
{{ trans('php.trans.args', ['name' => $name]) }}
{{ trans_choice('php.choice.args', $count, ['name' => $name]) }}
{{ __('Php args developer english :name', ['name' => $name]) }}

=== Collected: calls wrapped over several lines ===

{{
    __(
        'php.multiline.plain',
    )
}}
{{
    __(
        'php.multiline.args',
        ['name' => $name],
    )
}}
{{
    trans(
        'php.multiline.trans'
    )
}}

=== Collected: escaped quotes ===

{{ __('Php escaped \' apostrophe') }}
{{ __("Php escaped \" quote") }}
{{ __('Php escaped \' apostrophe with :name', ['name' => $name]) }}

=== Collected: a backslash that is not a quote escape ===

{{ __('Php backslash C:\\path') }}

=== Collected: blade directives ===

@lang('php.lang.directive')
@choice('php.choice.directive', $count)

=== Ignored: concatenation ===

{{ __('Php concat ' . 'single') }}
{{ __("Php concat " . "double") }}
{{ __('Php concat var ' . $a . ' single') }}
{{ __("Php concat var " . $a . " double") }}
{{ trans('php.concat.' . $suffix) }}

=== Ignored: empty strings ===

{{ __('') }}
{{ __("") }}

=== Ignored: method calls, not the global helper ===

{{ $translator->trans('php.method.call') }}
{{ $translator->__('Php method call') }}

=== Ignored: not a literal ===

{{ __($variableKey) }}
{{ trans($variableKey) }}
