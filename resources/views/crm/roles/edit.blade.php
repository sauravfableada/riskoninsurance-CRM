@extends('layouts.app')

@section('page_title', 'Edit Role')

@section('page_actions')
    <div class="d-flex gap-2">
        <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <h5 class="fw-bold mb-0">Edit Role: {{ ucfirst($role->name) }}</h5>
    </div>
    
    @if(in_array($role->name, ['Super Admin', 'Admin']))
        <div class="alert alert-info mx-4 mb-4 pb-2 pt-2">
            <i class="bi bi-info-circle me-1"></i> Note: You are editing a core system role. Some restrictions apply.
        </div>
    @endif

    <div class="card-body px-4 pb-4">
        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required 
                        @if(in_array($role->name, ['Super Admin', 'Admin'])) readonly @endif>
                    @if(in_array($role->name, ['Super Admin', 'Admin']))
                        <div class="form-text mt-1 text-muted"><i class="bi bi-lock"></i> Core system role names cannot be changed.</div>
                    @endif
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Assign Permissions</h6>
            <div class="row g-4">
                <div class="col-12">
                    @if($role->name === 'Super Admin')
                        <div class="alert alert-success py-3">
                            <i class="bi bi-check-circle-fill me-2"></i> The Super Admin role implicitly has ALL permissions. You do not need to assign them individually.
                        </div>
                    @else
                        @if($permissions->count() > 0)
                            <div class="row">
                                @foreach($permissions as $permission)
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" 
                                                {{ (is_array(old('permissions')) && in_array($permission->name, old('permissions'))) || (!old('permissions') && in_array($permission->name, $rolePermissions)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                {{ ucfirst(str_replace('-', ' ', $permission->name)) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="bi bi-exclamation-triangle"></i> No permissions found in the system.
                            </div>
                        @endif
                    @endif
                    @error('permissions')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-5 pt-3 border-top">
                <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-dark-blue px-4">Update Role</button>
            </div>
        </form>
    </div>
</div>
@endsection
