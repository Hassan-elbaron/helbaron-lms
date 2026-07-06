<?php

/*
 | Authoring domain configuration (curriculum + media metadata). No business rules here.
 */
return [
    'publish' => [
        // A course may publish only when it has at least one published lesson.
        'require_published_lesson' => true,
    ],
    'preview' => [
        'default' => false,
    ],
];
