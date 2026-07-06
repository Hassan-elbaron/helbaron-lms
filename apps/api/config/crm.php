<?php

/*
 | CRM domain configuration. Independent of Learning/Commerce. No marketing/AI/reports.
 */
return [
    'consulting' => [
        'sla_hours' => (int) env('CRM_CONSULTING_SLA_HOURS', 48),
    ],
    'search' => [
        'min_query_length' => 2,
    ],
    'pipeline' => [
        'default_stages' => ['New', 'Contacted', 'Qualified', 'Proposal', 'Won', 'Lost'],
    ],
];
