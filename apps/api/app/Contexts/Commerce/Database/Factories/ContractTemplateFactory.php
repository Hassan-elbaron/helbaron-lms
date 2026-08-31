<?php

namespace App\Contexts\Commerce\Database\Factories;

use App\Contexts\Commerce\Models\ContractTemplate;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractTemplate>
 */
class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    public function definition(): array
    {
        $brand = trim(app(BrandProfilePort::class)->profile()->name);
        $brand = $brand !== '' ? $brand : 'the academy';

        return [
            'key' => 'terms',
            'version' => 1,
            'title' => 'Terms & Conditions',
            'body' => 'By enrolling you accept the '.$brand.' terms and conditions.',
            'is_active' => true,
        ];
    }
}
