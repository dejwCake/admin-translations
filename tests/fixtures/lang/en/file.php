<?php

declare(strict_types=1);

return [
    'key' => 'en value',
    '404' => [
        'title' => 'page not found',
        'message' => 'This page does not exists',
    ],
    // A container declared empty, as `validation.attributes` is in a stock Laravel install.
    // `Arr::dot` cannot descend into it, so it surfaces as a leaf and must not become a key.
    'attributes' => [],
];
