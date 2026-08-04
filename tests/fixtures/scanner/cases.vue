<script setup lang="ts">
// Vue/TypeScript call shapes. `resources/js` is a scanned directory in real projects, and
// Prettier wraps long strings, which is what produces the multi-line calls below.
import { __ } from '@/shared/composables/useTranslations';

// Collected: plain and with replacement parameters
const plain = __('Vue plain');
const withArgs = __('Vue with :name', { name: props.name });

// Collected: Prettier-wrapped, with a trailing comma
const wrapped = __(
    'Vue wrapped over several lines because the string is long enough that Prettier breaks it',
);
const wrappedWithArgs = __(
    'Vue wrapped with :name',
    { name: props.name },
);

// Collected: escaped quotes
const escapedSingle = __('Vue escaped \' apostrophe');
const escapedDouble = __("Vue escaped \" quote");

// Ignored: concatenation and non-literals
const concat = __('Vue concat ' + 'parts');
const variable = __(someKey);

// Ignored: a method that happens to share the helper's name. JS uses dot syntax where
// PHP uses `->`, so both spellings have to be guarded against.
const method = i18n.__('Vue method call');
const nested = this.i18n.trans('vue.method.trans');
</script>

<template>
    <!-- Collected: template interpolation -->
    <p>{{ __('Vue in template') }}</p>
    <p>{{ __('Vue in template with :name', { name: props.name }) }}</p>
    <button :aria-label="__('Vue in attribute')">{{ plain }}</button>
</template>
