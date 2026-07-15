<?php

namespace App\Contexts\Commerce\Services;

use App\Contexts\Commerce\Models\Contract;
use App\Contexts\Commerce\Models\ContractTemplate;
use App\Contexts\Commerce\Models\Order;
use App\Platform\Shared\Services\BaseService;

/**
 * Resolves the active contract template version and creates per-order contract instances.
 * Acceptance itself (with audit) is performed by AcceptContractAction.
 */
class ContractService extends BaseService
{
    public function activeTemplate(?string $key = null): ?ContractTemplate
    {
        $key ??= (string) config('commerce.contract.required_key', 'terms');

        return ContractTemplate::where('key', $key)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    public function createForOrderByUserId(int $userId, Order $order): ?Contract
    {
        $template = $this->activeTemplate();

        if ($template === null) {
            return null; // no contract required if none is configured/active
        }

        return Contract::create([
            'user_id' => $userId,
            'order_id' => $order->id,
            'template_id' => $template->id,
            'status' => 'pending',
        ]);
    }
}
