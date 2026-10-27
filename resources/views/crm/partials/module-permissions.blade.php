<script>
window.crmUserPermissions = {
    ...(window.crmUserPermissions || {}),
    {{ $module }}: {
        @foreach($permissions as $ability => $value)
        {{ $ability }}: @json($value)@if(!$loop->last),@endif
        @endforeach
    }
};
</script>
