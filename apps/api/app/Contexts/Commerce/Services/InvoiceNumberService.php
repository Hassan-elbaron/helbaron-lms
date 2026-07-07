<?php

namespace App\Contexts\Commerce\Services;

use App\Contexts\Commerce\Models\Invoice;
use App\Platform\Shared\Services\BaseService;

/**
 * Allocates a unique, human-readable invoice number, e.g. INV-2025-000123.
 */
class InvoiceNumberService extends BaseService
{
    public function next(): string
    {
        $prefix = (string) config('commerce.invoice.prefix', 'INV');
        $year = now()->format('Y');
        $sequence = Invoice::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
