<?php

namespace App\Services;

class RenewalMessageTemplates
{
    public function all(): array
    {
        return [[
            'name' => 'Policy renewal reminder',
            'message' => "Dear {holder_name},\n\nGreetings from {company_name}.\n\nYour {policy_type} policy is due for renewal.\n\nPolicy number: {policy_number}\nInsurance company: {insurer}\nCurrent recorded premium: {premium}\nRenewal due: {expiry_date}\n\nPlease contact us before expiry to renew your policy and maintain uninterrupted coverage. Renewal premium and coverage are subject to confirmation by the insurer.\n\nIf already renewed, please share the updated policy document.\n\nThank you,\n{company_name}",
        ]];
    }
}
