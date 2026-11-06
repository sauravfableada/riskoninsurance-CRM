<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FamilyGroupMember;
use App\Models\Setting;

class CelebrationWhatsappWish
{
    public function draft(string $occasion, Customer|FamilyGroupMember $person, ?Customer $holder = null): ?array
    {
        $phones = new PolicyWhatsappReminder;
        $phone = $person instanceof Customer ? $phones->phone($person) : $phones->phone(new Customer([
            'phone' => $person->contact,
            'country_code' => $holder?->country_code,
        ]));
        $phone ??= $holder ? $phones->phone($holder) : null;
        if (!$phone) return null;

        $name = $person instanceof Customer ? $person->name : $person->member_name;
        $brand = Setting::where('key', 'company_name')->value('value') ?: config('app.name');
        $wish = $occasion === 'birthdays'
            ? 'Wishing you a very happy birthday! May your year ahead be filled with happiness, good health, and wonderful moments.'
            : 'Wishing you a very happy anniversary! May your journey together be filled with love, happiness, and beautiful memories.';

        return ['phone' => $phone, 'holder' => $name, 'message' => "Dear {$name},\n\n{$wish}\n\nWarm wishes,\n{$brand}"];
    }
}
