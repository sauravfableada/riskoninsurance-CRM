<div class="modal fade" id="todaysOverviewModal" tabindex="-1" aria-labelledby="todaysOverviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable overview-dialog">
        <div class="modal-content overview-modal">
            <div class="modal-header overview-modal-header">
                <div>
                    <p class="overview-eyebrow">YOUR INSURANCE CRM AT A GLANCE</p>
                    <h5 class="modal-title fw-bold" id="todaysOverviewLabel">Today's overview · {{ $todaysOverview['date'] }}</h5>
                    <p class="text-muted overview-description">Your schedule, work needing attention, and customer celebrations.</p>
                </div>
                <button type="button" class="btn-close overview-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="overview-columns">
                    @foreach($todaysOverview['sections'] as $key => $section)
                        <section class="overview-column" aria-labelledby="overview-{{ $key }}">
                            <div class="overview-column-heading">
                                <h6 id="overview-{{ $key }}"><i class="bi bi-{{ $section['icon'] }} me-1" aria-hidden="true"></i>{{ $section['title'] }}</h6>
                                <span class="overview-count" aria-label="{{ $section['items']->count() }} items">{{ $section['items']->count() }}</span>
                            </div>
                            <div class="overview-items" tabindex="0" role="region" aria-label="{{ $section['title'] }}">
                                @forelse($section['items'] as $item)
                                    <div class="overview-item {{ $item['overdue'] ? 'overview-item-overdue' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                                            <a class="text-decoration-none text-reset" href="{{ $item['url'] }}"><strong>{{ $item['title'] }}</strong></a>
                                            @if(!empty($item['whatsapp']))
                                                <button type="button" class="celebration-whatsapp-wish" data-whatsapp-draft="{{ json_encode($item['whatsapp']) }}" aria-label="Preview WhatsApp wish for {{ $item['title'] }}" title="Wish on WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></button>
                                            @endif
                                        </div>
                                        @if($item['detail'])<span class="text-muted">{{ $item['detail'] }}</span>@endif
                                        <span>{{ $item['date'] }}</span>
                                        <span class="overview-item-badge">{{ $item['badge'] }}</span>
                                    </div>
                                @empty
                                    <p class="overview-empty">{{ $section['empty'] }}</p>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #todaysOverviewModal .overview-dialog { max-width: 1480px; width: calc(100% - 96px); margin-left: auto; margin-right: auto; }
    #todaysOverviewModal .overview-modal { border: 0; border-radius: 24px; overflow: hidden; color: #203650; background: #fff; }
    #todaysOverviewModal .overview-modal-header { position: relative; display: flex; justify-content: space-between; background: #eef3fa; padding: 16px 68px 16px 24px; align-items: center; }
    #todaysOverviewModal .overview-close { position: absolute; right: 24px; top: 50%; transform: translateY(-50%); margin: 0 !important; flex-shrink: 0; }
    #todaysOverviewModal .overview-eyebrow { color: #496b94; font-size: .65rem; line-height: 1.4; font-weight: 800; letter-spacing: .1em; margin: 0 0 6px; }
    #todaysOverviewModal .overview-modal-header .modal-title { font-size: 1.05rem; line-height: 1.35; margin: 0; }
    #todaysOverviewModal .overview-description { font-size: .75rem; line-height: 1.45; margin: 6px 0 0; }
    #todaysOverviewModal .overview-columns { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
    #todaysOverviewModal .overview-column { background: #f5f7fa; border: 1px solid #dce3ed; border-radius: 17px; padding: 14px; min-width: 0; }
    #todaysOverviewModal .overview-column-heading { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 6px 0 20px; }
    #todaysOverviewModal .overview-column-heading h6 { font-size: .85rem; font-weight: 700; margin: 0; }
    #todaysOverviewModal .overview-columns { font-size: .8rem; line-height: 1.5; }
    #todaysOverviewModal .overview-count { font-size: .8rem; }
    #todaysOverviewModal .overview-item > span { font-size: .75rem; }
    #todaysOverviewModal .overview-count { border-radius: 30px; min-width: 32px; padding: 5px 9px; text-align: center; background: #1d406e; color: #fff; font-weight: 700; }
    #todaysOverviewModal .overview-items { height: min(38vh, 340px); overflow-y: auto; scrollbar-width: thin; padding-right: 4px; }
    #todaysOverviewModal .overview-item { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; border: 1px solid #d6dfeb; border-left: 3px solid #6588b7; border-radius: 14px; background: #fff; padding: 15px; margin-bottom: 10px; text-decoration: none; color: #203650; font-size: .85rem; overflow-wrap: anywhere; }
    #todaysOverviewModal .overview-item:hover { background: #edf3fb; border-color: #6588b7; }
    #todaysOverviewModal .overview-item strong { font-size: .85rem; }
    #todaysOverviewModal .celebration-whatsapp-wish { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 30px; width: 30px; height: 30px; border: 1px solid #9cd6b4; border-radius: 9px; background: #eaf8ef; color: #18804c; font-size: 16px; cursor: pointer; }
    #todaysOverviewModal .overview-item-badge { border-radius: 20px; background: #e8eef7; color: #355d91; padding: 5px 10px; font-size: .8rem; }
    #todaysOverviewModal .overview-item-overdue .overview-item-badge { color: #a43d30; background: #fff0ed; }
    #todaysOverviewModal .overview-empty { border: 1px dashed #bdcce0; border-radius: 14px; padding: 20px 16px; color: #65758c; font-size: .8rem; }
    @media (max-width: 1199px) { #todaysOverviewModal .overview-columns { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767px) { #todaysOverviewModal .overview-columns { grid-template-columns: 1fr; } #todaysOverviewModal .overview-dialog { width: calc(100% - 20px); } #todaysOverviewModal .overview-items { height: auto; max-height: 280px; } }
</style>
@endpush

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') : '') . '/js/policy-whatsapp.js' }}?v={{ hash_file('sha256', public_path('js/policy-whatsapp.js')) }}" defer></script>
@endpush
