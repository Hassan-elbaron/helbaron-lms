<?php

namespace App\Domains\Crm\Actions\Organization;

use App\Domains\Crm\Events\OrganizationCreated;
use App\Domains\Crm\Models\Organization;
use App\Platform\Shared\Actions\BaseAction;
use App\Platform\Shared\Helpers\Slug;

class CreateOrganizationAction extends BaseAction
{
    /** @param array<string, mixed> $data */
    public function execute(array $data): Organization
    {
        $organization = $this->transaction(function () use ($data): Organization {
            return Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Slug::make($data['name']).'-'.uniqid(),
                'status' => $data['status'] ?? 'prospect',
                'size' => $data['size'] ?? null,
                'website' => $data['website'] ?? null,
            ]);
        });

        OrganizationCreated::dispatch($organization);

        return $organization;
    }
}
