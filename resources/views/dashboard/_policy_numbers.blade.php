@php
    $policyNumbers = collect([
        $policy['policy_number_no'] ?? null,
    ])->filter(fn ($number) => filled($number));
@endphp

@if($policyNumbers->isNotEmpty())
    <div class="policy-number-badges">
        @foreach($policyNumbers as $number)
            <a href="{{ route('policies.view', $policy['id']) }}" class="policy-number-badge">{{ $number }}</a>
        @endforeach
    </div>
@else
    <span class="text-muted">--</span>
@endif
