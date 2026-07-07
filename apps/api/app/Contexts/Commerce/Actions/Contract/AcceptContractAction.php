<?php

namespace App\Contexts\Commerce\Actions\Contract;

use App\Contexts\Commerce\Enums\ContractStatus;
use App\Contexts\Commerce\Events\ContractAccepted;
use App\Contexts\Commerce\Exceptions\ContractAlreadyAcceptedException;
use App\Contexts\Commerce\Models\Contract;
use App\Contexts\Commerce\Models\ContractAcceptance;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Records an immutable acceptance (template version + body hash + actor context) and marks the
 * contract accepted. Dispatches ContractAccepted after commit (which can unblock fulfillment).
 */
class AcceptContractAction extends BaseAction
{
    /**
     * @param  array{ip?: ?string, user_agent?: ?string}  $context
     */
    public function execute(Contract $contract, array $context = []): Contract
    {
        if ($contract->isAccepted()) {
            throw new ContractAlreadyAcceptedException;
        }

        $contract = $this->transaction(function () use ($contract, $context): Contract {
            $template = $contract->template;

            ContractAcceptance::create([
                'contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'template_version' => $template->version,
                'body_hash' => $template->bodyHash(),
                'ip' => $context['ip'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'accepted_at' => now(),
            ]);

            $contract->forceFill([
                'status' => ContractStatus::Accepted->value,
                'accepted_at' => now(),
            ])->save();

            return $contract;
        });

        ContractAccepted::dispatch($contract);

        return $contract;
    }
}
