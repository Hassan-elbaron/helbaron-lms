<?php

namespace App\Domains\Commerce\Services;

use App\Domains\Commerce\Models\Contract;
use App\Domains\Commerce\Models\ContractTemplate;
use App\Domains\Commerce\Models\Order;
use App\Platform\Identity\Models\User;
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

    public function createForOrder(User $user, Order $order): ?Contract
    {
        $template = $this->activeTemplate();

        if ($template === null) {
            return null; // no contract required if none is configured/active
        }

        return Contract::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'template_id' => $template->id,
            'status' => 'pending',
        ]);
    }
}
