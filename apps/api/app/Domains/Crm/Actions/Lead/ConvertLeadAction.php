<?php

namespace App\Domains\Crm\Actions\Lead;

use App\Domains\Crm\Enums\LeadStatus;
use App\Domains\Crm\Events\LeadConverted;
use App\Domains\Crm\Exceptions\LeadAlreadyConvertedException;
use App\Domains\Crm\Models\Contact;
use App\Domains\Crm\Models\Lead;
use App\Shared\Actions\BaseAction;

/**
 * Converts a qualified lead into a Contact and marks the lead converted. Guarded against
 * double-conversion.
 */
class ConvertLeadAction extends BaseAction
{
    public function execute(Lead $lead): Contact
    {
        if ($lead->isConverted()) {
            throw new LeadAlreadyConvertedException;
        }

        $contact = $this->transaction(function () use ($lead): Contact {
            $parts = explode(' ', trim($lead->name), 2);

            $contact = Contact::create([
                'company_id' => $lead->company_id,
                'first_name' => $parts[0] ?? $lead->name,
                'last_name' => $parts[1] ?? null,
                'email' => $lead->email,
                'phone' => $lead->phone,
            ]);

            $lead->forceFill(['status' => LeadStatus::Converted->value, 'contact_id' => $contact->id])->save();

            return $contact;
        });

        LeadConverted::dispatch($lead->refresh());

        return $contact;
    }
}
