<div class="crm-settings-sidebar h-100 py-3">
    <div
        class="crm-settings-sidebar-header fw-bold px-3 mb-2 text-uppercase text-muted"
        style="font-size: 0.75rem; letter-spacing: 0.1em;"
    >
        Masters
    </div>
    <div id="masters-group" class="list-group list-group-flush border-0">
        <a href="{{ route('masters.countries.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.countries.*')) active fw-medium @endif">
            Countries
        </a>
        <a href="{{ route('masters.cities.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.cities.*')) active fw-medium @endif">
            Cities
        </a>
        <a href="{{ route('masters.lead_sources.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.lead_sources.*')) active fw-medium @endif">
            Lead Sources
        </a>
        <a href="{{ route('masters.stages.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.stages.*')) active fw-medium @endif">
            Stages
        </a>
        <a href="{{ route('masters.travel_types.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.travel_types.*')) active fw-medium @endif">
            Travel Types
        </a>
        <a href="{{ route('masters.room_categories.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.room_categories.*')) active fw-medium @endif">
            Room Categories
        </a>
        <a href="{{ route('masters.transport_types.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.transport_types.*')) active fw-medium @endif">
            Transport Types
        </a>
    </div>

    <div
        class="crm-settings-section-header fw-bold px-3 mb-2 mt-4 text-uppercase text-muted"
        style="font-size: 0.75rem; letter-spacing: 0.1em;"
    >
        Partners & Users
    </div>
    <div id="partners-group" class="list-group list-group-flush border-0">
        <a href="{{ route('masters.suppliers.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.suppliers.*')) active fw-medium @endif">
            Suppliers
        </a>
        <a href="{{ route('masters.hotels.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.hotels.*')) active fw-medium @endif">
            Hotels
        </a>
        <a href="{{ route('masters.agents.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.agents.*')) active fw-medium @endif">
            Agents
        </a>
        <a href="{{ route('masters.customers.index') }}"
           class="list-group-item list-group-item-action border-0 @if(request()->routeIs('masters.customers.*')) active fw-medium @endif">
            Customers
        </a>
    </div>
</div>
