@extends('layouts.app')

@section('page_title', 'Tasks - Create')

@section('content')
    <div class="container-fluid p-0">

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden task-form-card">
            <div class="card-header bg-white border-bottom py-3 px-3 px-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h1 class="h4 mb-1 fw-semibold">Add Task</h1>
                        <p class="text-muted small mb-0">Create a new task for the team.</p>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-dark-blue task-back-btn">
                        <i class="fa-solid fa-angle-left pe-1"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <form method="POST" action="/api/tasks" id="taskForm" class="needs-validation ajax-task-form" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Policy Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="policy_type_id" id="policy_type_id"
                                        class="form-select @error('policy_type_id') is-invalid @enderror" required>
                                        <option value="">-- Select Policy Type --</option>
                                        @foreach($policyTypes as $type)
                                            <option value="{{ $type->id }}" @selected(old('policy_type_id') == $type->id)>
                                                {{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="policy_type_id-error">{{ $errors->first('policy_type_id') }}</div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddPolicyType" title="Add Policy Type"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Policy Sub Type <span class="text-muted small fw-normal">(Optional)</span></label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="policy_sub_type_id" id="policy_sub_type_id"
                                        data-selected="{{ old('policy_sub_type_id') }}"
                                        class="form-select @error('policy_sub_type_id') is-invalid @enderror">
                                        <option value="">-- Select Policy Sub Type --</option>
                                    </select>
                                    <div class="invalid-feedback" id="policy_sub_type_id-error">{{ $errors->first('policy_sub_type_id') }}</div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddPolicySubType" title="Add Policy Sub Type"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            @include('crm.partials.assigned-user-field', ['users' => $users, 'selected' => auth()->id()])
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Assigned For <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <select name="related_id" id="related_id"
                                        class="form-select @error('related_id') is-invalid @enderror"
                                        data-search-url="{{ route('api.customers.search') }}" data-search-type="customer"
                                        data-search-placeholder="-- Search Customer --" required>
                                        <option value="">-- Search Customer --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" data-email="{{ $customer->email }}"
                                                data-phone="{{ $customer->phone }}" @selected(old('related_id') == $customer->id)>
                                                {{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="related_id-error">{{ $errors->first('related_id') }}</div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-dark-blue px-3" data-bs-toggle="modal" data-bs-target="#modalAddCustomer" title="Add New Customer"><i class="fa-solid fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Task Title <span class="text-danger">*</span></label>
                            <input name="title" id="title" value="{{ old('title') }}"
                                class="form-control @error('title') is-invalid @enderror" required>
                            <div class="invalid-feedback" id="title-error">{{ $errors->first('title') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="text" name="due_date" id="due_date" value="{{ old('due_date') }}"
                                placeholder="dd/mm/yyyy" min="{{ date('Y-m-d') }}"
                                class="form-control js-date @error('due_date') is-invalid @enderror">
                            <div class="invalid-feedback" id="due_date-error">{{ $errors->first('due_date') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" id="priority"
                                class="form-select @error('priority') is-invalid @enderror" required>
                                <option value="">Select Priority</option>
                                <option value="low" @selected(old('priority') === 'low')>Low</option>
                                <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium</option>
                                <option value="high" @selected(old('priority') === 'high')>High</option>
                            </select>
                            <div class="invalid-feedback" id="priority-error">{{ $errors->first('priority') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror"
                                required>
                                <option value="">Select Status</option>
                                <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                                <option value="in_progress" @selected(old('status', 'in_progress') === 'in_progress')>In Progress</option>
                                <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                            </select>
                            <div class="invalid-feedback" id="status-error">{{ $errors->first('status') }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2"
                                class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            <div class="invalid-feedback" id="description-error">{{ $errors->first('description') }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top d-flex flex-sm-row justify-content-end gap-2 form-actions">
                        <a href="{{ route('tasks.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                        <button type="submit" class="btn btn-dark-blue" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"
                                id="btnSpinner"></span>
                            <span id="btnText">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('crm.tasks.partials.policy-type-modals')

    <!-- Modal: Add Customer -->
    <div class="modal fade" id="modalAddCustomer" tabindex="-1" aria-labelledby="modalAddCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:#0a2540;">
                    <h5 class="modal-title text-white fw-bold" id="modalAddCustomerLabel">Add Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAddCustomer" action="/api/customers" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Full name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" list="quickCustomerCountryCodes" maxlength="5" style="width:95px" aria-label="Country code">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone number" maxlength="15" inputmode="numeric">
                            </div>
                            <datalist id="quickCustomerCountryCodes"><option value="+91"><option value="+1"><option value="+44"><option value="+61"><option value="+971"><option value="+966"><option value="+65"><option value="+60"><option value="+974"></datalist>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark-blue">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span class="btn-text">Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
        <style>
            .project-option { display:flex; align-items:center; justify-content:space-between; gap:.75rem; width:100%; }
            .project-option-delete { border:0; background:transparent; color:#dc3545; padding:.1rem .35rem; line-height:1; font-size:1.25rem; border-radius:.25rem; }
            .project-option-delete:hover { background:#fee2e2; color:#b91c1c; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/js/tasks.js' : '/js/tasks.js') }}?v={{ filemtime(public_path('js/tasks.js')) }}"></script>
    @endpush
@endsection
