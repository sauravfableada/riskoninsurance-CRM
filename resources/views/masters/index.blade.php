@extends('layouts.masters')

@section('page_title', 'Masters Dashboard')

@section('masters_content')
    <div class="container-fluid p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-0">Masters Dashboard</h4>
                <p class="text-muted small mb-0">Manage all master data from one place.</p>
            </div>
            <div class="master-search-wrap position-relative">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="masterSearchInput" class="form-control border-start-0 ps-0"
                        placeholder="Search masters..." autocomplete="off" aria-label="Search masters"
                        aria-controls="masterSearchSuggestions">
                    <button type="button" id="masterSearchClear" class="btn btn-outline-secondary d-none" aria-label="Clear search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div id="masterSearchSuggestions" class="master-search-suggestions d-none" role="listbox"></div>
            </div>
        </div>

        <div class="row g-4" id="mastersGrid">
            <!-- Insurance Policy Type Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.insurance_policy_types.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark-text fs-1 text-info"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Insurance Policy Type Master</h6>
                            <p class="text-muted small mb-0">Manage policy types</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Insurance Policy Sub Type Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.insurance_policy_sub_types.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-files fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Insurance Policy Sub Type Master</h6>
                            <p class="text-muted small mb-0">Manage policy sub types</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Segment Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.segments.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-pie-chart-fill fs-1 text-danger"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Segment Master</h6>
                            <p class="text-muted small mb-0">Manage segments</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Branch Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.branches.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-shop fs-1 text-primary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Branch Master</h6>
                            <p class="text-muted small mb-0">Manage branches</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Company Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.company_master.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-building fs-1 text-secondary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Company Master</h6>
                            <p class="text-muted small mb-0">Manage companies</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Agency Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.agencies.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-house-door fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Agency Master</h6>
                            <p class="text-muted small mb-0">Manage agencies</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Agency Level Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.agency_levels.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-layers fs-1 text-info"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Agency Level Master</h6>
                            <p class="text-muted small mb-0">Manage agency levels</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Broker Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.brokers.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-person-badge fs-1 text-warning"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Broker Master</h6>
                            <p class="text-muted small mb-0">Manage brokers</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Division Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.divisions.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-grid-3x3 fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Division Master</h6>
                            <p class="text-muted small mb-0">Manage divisions</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Bank Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.banks.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-credit-card fs-1 text-dark"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Bank Master</h6>
                            <p class="text-muted small mb-0">Manage banks</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Document Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.documents.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark fs-1 text-secondary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Document Master</h6>
                            <p class="text-muted small mb-0">Manage documents</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Document Type Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.document_types.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark-check fs-1 text-primary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Document Type Master</h6>
                            <p class="text-muted small mb-0">Manage document types</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Bond Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.bonds.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-briefcase fs-1 text-warning"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Bond Master</h6>
                            <p class="text-muted small mb-0">Manage investment bonds</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Family Groups Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.family_groups.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-people-fill fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Family Groups Master</h6>
                            <p class="text-muted small mb-0">Manage family groups</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Investment Sub Fund Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.investment_sub_fund_master.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark-plus fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Investment Sub Fund Master</h6>
                            <p class="text-muted small mb-0">Manage investment sub-funds</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Investment Type -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.investment_type.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-bar-chart-fill fs-1 text-info"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Investment Type</h6>
                            <p class="text-muted small mb-0">Manage investment types</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Business Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.business_master.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-briefcase-fill fs-1 text-primary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Business Master</h6>
                            <p class="text-muted small mb-0">Manage business types</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Client Status -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.client_status_master.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-person-check-fill fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Client Status</h6>
                            <p class="text-muted small mb-0">Manage client status</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Commission -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('commission-rules.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-cash-stack fs-1 text-danger"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Commission</h6>
                            <p class="text-muted small mb-0">Manage commissions</p>
                        </div>
                    </div>
                </a>
            </div>

            @if(auth()->user()?->isAdmin())
            <!-- Sub Agent -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('users.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-person-badge-fill fs-1 text-primary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Sub Agent</h6>
                            <p class="text-muted small mb-0">Manage sub-agents</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Team Members -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('team-members.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-people fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Team Members</h6>
                            <p class="text-muted small mb-0">Manage team</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Team Member Designations -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.team_member_designations.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3"><i class="bi bi-person-workspace fs-1 text-info"></i></div>
                            <h6 class="fw-bold text-dark mb-1">Team Member Designation</h6>
                            <p class="text-muted small mb-0">Manage staff designations</p>
                        </div>
                    </div>
                </a>
            </div>
            @endif

            <!-- Investment Fund Type Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.fund_type.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-wallet2 fs-1 text-primary"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Investment Fund Type Master</h6>
                            <p class="text-muted small mb-0">Master Fund Type</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Investment Party Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.invested_party.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-stars fs-1 text-warning"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Investment Party Master</h6>
                            <p class="text-muted small mb-0">Party Master</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Investment Post Office Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.post_office.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-mailbox fs-1 text-info"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Investment Post Office Master</h6>
                            <p class="text-muted small mb-0">Master Post Office</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Payment Method Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.payment_method.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-credit-card-2-back-fill fs-1 text-warning"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Payment Method Master</h6>
                            <p class="text-muted small mb-0">Master Payment Method</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Project Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('projects.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-kanban-fill fs-1 text-success"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Project Master</h6>
                            <p class="text-muted small mb-0">Manage Projects</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Area Master -->
            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                <a href="{{ route('masters.area.index') }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-map-fill fs-1 text-danger"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Areas Master</h6>
                            <p class="text-muted small mb-0">Areas Master</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div id="masterSearchEmpty" class="text-center py-5 d-none">
            <i class="fa-solid fa-magnifying-glass text-muted fs-2 mb-3"></i>
            <h6 class="fw-bold">No masters found</h6>
            <p class="text-muted small mb-0">Try another search term.</p>
        </div>
    </div>

    <style>
        .hover-shadow:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            transform: translateY(-2px);
        }
        .transition-all {
            transition: all 0.3s ease;
        }
        .master-search-wrap { width: min(100%, 370px); z-index: 20; }
        .master-search-wrap .input-group { box-shadow: 0 4px 14px rgba(15, 39, 71, .08); }
        .master-search-wrap .form-control,
        .master-search-wrap .input-group-text,
        .master-search-wrap .btn { min-height: 44px; }
        .master-search-suggestions {
            position: absolute;
            top: calc(100% + 7px);
            left: 0;
            right: 0;
            max-height: 340px;
            overflow-y: auto;
            padding: .45rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 16px 35px rgba(15, 23, 42, .14);
        }
        .master-suggestion {
            display: flex;
            align-items: center;
            gap: .75rem;
            width: 100%;
            padding: .7rem .75rem;
            border: 0;
            border-radius: 9px;
            color: #0f2747;
            background: transparent;
            text-align: left;
            text-decoration: none;
        }
        .master-suggestion:hover,
        .master-suggestion:focus { background: #eef4fb; color: #0f2747; }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('masterSearchInput');
            const clearButton = document.getElementById('masterSearchClear');
            const suggestions = document.getElementById('masterSearchSuggestions');
            const emptyState = document.getElementById('masterSearchEmpty');
            const cards = Array.from(document.querySelectorAll('#mastersGrid > div')).map(function (column) {
                const link = column.querySelector('a[href]');
                const title = column.querySelector('h6')?.textContent.trim() || '';
                const description = column.querySelector('p')?.textContent.trim() || '';
                const icon = column.querySelector('i')?.className || 'fa-solid fa-table-list';

                return { column, link, title, description, icon, search: (title + ' ' + description).toLowerCase() };
            });
            const virtualMasters = [
                {
                    column: null,
                    link: { href: @json(route('policies.legacy.index')) },
                    title: 'Policy',
                    description: 'Manage insurance policies',
                    icon: 'fa-solid fa-file-signature text-warning',
                    search: 'policy manage insurance policies'
                }
            ];
            const searchItems = [...virtualMasters, ...cards];

            function renderSearch() {
                const query = input.value.trim().toLowerCase();
                const matches = searchItems.filter(item => !query || item.search.includes(query));

                cards.forEach(item => item.column.classList.toggle('d-none', query !== '' && !item.search.includes(query)));
                emptyState.classList.toggle('d-none', matches.length !== 0);
                clearButton.classList.toggle('d-none', query === '');

                if (!query) {
                    suggestions.classList.add('d-none');
                    suggestions.innerHTML = '';
                    return;
                }

                suggestions.innerHTML = matches.length
                    ? matches.map(item => `
                        <a class="master-suggestion" href="${item.link.href}" role="option">
                            <span><strong class="d-block">${item.title}</strong><small class="text-muted">${item.description}</small></span>
                        </a>`).join('')
                    : '<div class="text-muted small text-center p-3">No matching masters</div>';
                suggestions.classList.remove('d-none');
            }

            input.addEventListener('input', renderSearch);
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    const firstMatch = suggestions.querySelector('a');
                    if (firstMatch) {
                        event.preventDefault();
                        window.location.href = firstMatch.href;
                    }
                }
            });
            input.addEventListener('focus', renderSearch);
            clearButton.addEventListener('click', function () {
                input.value = '';
                renderSearch();
                input.focus();
            });
            document.addEventListener('click', function (event) {
                if (!event.target.closest('.master-search-wrap')) suggestions.classList.add('d-none');
            });
        });
    </script>
    @endpush
@endsection
