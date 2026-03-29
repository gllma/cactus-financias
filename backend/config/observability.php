<?php

return [
    'allowlist' => array_values(array_filter(array_map(
        static fn (string $entry): string => trim($entry),
        explode(',', (string) env('OBSERVABILITY_ALLOWLIST', ''))
    ))),
];
