<?php

/*
 | Catalog domain configuration (listing, search, related). No business rules here.
 */
return [
    'pagination' => [
        'per_page' => (int) env('CATALOG_PER_PAGE', 15),
        'max_per_page' => 60,
    ],
    'related' => [
        'limit' => 8,
    ],
    'search' => [
        'min_query_length' => 2,
    ],
];
