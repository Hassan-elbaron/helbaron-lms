<?php

namespace App\Domains\Commerce\Database\Factories;

use App\Domains\Commerce\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractTemplate>
 */
class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    public function definition(): array
    {
        return [
            'key' => 'terms',
            'version' => 1,
            'title' => 'Terms & Conditions',
            'body' => 'By enrolling you accept the HElbaron terms and conditions.',
            'is_active' => true,
        ];
    }
}
