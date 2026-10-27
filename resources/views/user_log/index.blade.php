@extends('layouts.app')

@section('page_title', 'User Logs')

@push('styles')
    <link rel="stylesheet" href="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/css/user-logs.css' : '/css/user-logs.css') }}?v={{ filemtime(public_path('css/user-logs.css')) }}">
@endpush

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 w-100 pb-4">
                    <div class="flex-grow-1 w-100">
                        <h4 class="fw-bold mb-0">User Logs</h4>
                        <p class="text-muted small mb-0">Track user activity across CRM modules.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto justify-content-lg-end justify-content-md-end">
                        <button type="button" class="btn btn-outline-dark-blue flex-grow-1 flex-md-grow-0" id="userLogsRefreshBtn">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-dark-blue flex-grow-1 flex-md-grow-0" id="userLogsDeleteAllBtn">
                            <i class="bi bi-trash text-danger3 me-2"></i>Delete All
                        </button>
                    </div>
                </div>

                <div id="userLogsFilterForm" class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="per_page" class="mb-0">Show</label>
                        <select name="per_page" id="per_page" class="form-select form-select-sm w-auto">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        <span>entries</span>
                    </div>

                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-light border-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="userLogsSearch" name="q" value="{{ $search }}"
                            class="form-control bg-light border-0" placeholder="Search logs..." autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 responsive-table" id="userLogsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Actioned By</th>
                                <th>Module</th>
                                <th>Taken Action</th>
                                <th>Message</th>
                                <th>Created At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">Loading user logs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white border-top-0 py-3 px-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="text-muted small" id="userLogsSummary">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <div id="userLogsPagination"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.userLogsConfig = {
            indexUrl: @json(route('api.user-logs.index')),
            destroyBaseUrl: @json(url('/api/user-logs')),
            destroyAllUrl: @json(route('api.user-logs.destroy_all')),
        };
    </script>
    <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/user-logs.js' : '/js/user-logs.js') }}?v={{ filemtime(public_path('js/user-logs.js')) }}"></script>
@endpush