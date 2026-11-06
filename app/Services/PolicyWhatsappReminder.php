<?php

namespace App\Services;

use App\Models\{Policy, User, Setting};
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PolicyWhatsappReminder
{
    public function authorize(Policy $policy, User $user): void
    {
        abort_unless($user->isAdmin() || $user->hasMatrixPermission('view_policies'),403);
        abort_unless(($user->isAdmin() && $user->type !== 'sub_agents') || (int)$policy->user_id === (int)$user->id || (int)$policy->created_by === (int)$user->id,403);
        abort_if($policy->deleted_at !== null,404);
    }

    public function days(?string $expiry): ?int
    {
        if (!$expiry) return null;
        try { return (int) now()->startOfDay()->diffInDays(Carbon::parse($expiry)->startOfDay(), false); }
        catch (\Throwable $e) { return null; }
    }

    public function phone($holder): ?string
    {
        if (!$holder) return null;
        $whatsapp = trim((string)$holder->whatsapp);
        $raw = $whatsapp !== '' ? $whatsapp : trim((string)$holder->phone);
        $code = $whatsapp !== '' ? $holder->whatsapp_country_code : $holder->country_code;
        if (!$raw || !preg_match('/^\+?[\d\s().-]+$/', $raw)) return null;
        $number = preg_replace('/\D/', '', $raw);
        if (str_starts_with($raw,'+')) { /* Already international. */ }
        elseif (str_starts_with($number,'00')) $number = substr($number,2);
        elseif ($code) {
            $prefix=preg_replace('/\D/','',(string)$code);
            if (!(str_starts_with($number,$prefix) && strlen($number)>10)) $number=$prefix.ltrim($number,'0');
        } elseif (strlen($number) === 10) $number='91'.$number;
        else return null;
        return preg_match('/^[1-9]\d{7,14}$/',$number) ? $number : null;
    }

    public function draft(Policy $policy): array
    {
        $days=$this->days($policy->end_date);
        if ($days === null || $days < 0 || $days > 90 || !Policy::currentTerms()->whereKey($policy->id)->exists()) {
            throw ValidationException::withMessages(['policy'=>'Renewal reminders are available for current policies expiring today or within the next 90 days.']);
        }
        $policy->loadMissing(['holder','company','policyType']);
        $phone=$this->phone($policy->holder);
        if (!$phone) throw ValidationException::withMessages(['phone'=>'Add a valid WhatsApp or phone number with country code to the policyholder record first.']);
        $brand=Setting::where('key','company_name')->value('value') ?: config('app.name');
        $name=$policy->holder?->name ?: 'Customer';
        $product=$policy->product_name ?: $policy->policyType?->name ?: 'Insurance policy';
        $number=$policy->policy_number_no ?: $policy->policy_number;
        $insurer=$policy->company?->name ?: 'Not recorded';
        $premium=$policy->premium_amount !== null ? 'INR '.number_format((float)$policy->premium_amount,2) : 'Not recorded';
        $date=Carbon::parse($policy->end_date)->format('d M Y');
        $values = ['{holder_name}' => $name, '{company_name}' => $brand, '{policy_type}' => $product,
            '{policy_number}' => $number, '{insurer}' => $insurer, '{premium}' => $premium,
            '{expiry_date}' => $date, '{days_remaining}' => (string) $days];
        $templates = array_map(fn ($template) => ['name' => $template['name'],
            'message' => strtr($template['message'], $values)], (new RenewalMessageTemplates)->all());
        return ['phone'=>$phone,'message'=>$templates[0]['message'],'templates'=>$templates,'holder'=>$name,'days'=>$days];
    }
}
