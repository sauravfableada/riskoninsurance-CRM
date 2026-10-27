@extends('layouts.app')

@section('page_title', 'Roles & Permissions')

@section('page_actions')
    <a href="{{ route('roles.create') }}" class="btn btn-dark-blue btn-sm rounded-pill px-3">
        <i class="bi bi-shield-plus me-1"></i> Add Role
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">System Roles</h5>
        </div>
    </div>
    
    @if(session('success'))
        <div class="px-4">
            <div class="alert alert-success alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close pb-2 pt-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="px-4">
            <div class="alert alert-danger alert-dismissible fade show pb-2 pt-2 mb-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close pb-2 pt-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Role Name</th>
                        <th>Permissions Attached</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">
                                {{ ucfirst($role->name) }}
                            </td>
                            <td>
                                @if($role->name === 'Super Admin')
                                    <span class="badge bg-success rounded-pill px-3">All Permissions</span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 me-1">
                                        {{ $role->permissions->count() }} Permissions
                                    </span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-light rounded-circle me-1" title="Edit">
                                    <i class="bi bi-pencil-square text-primary"></i>
                                </a>
                                @if(!in_array($role->name, ['Super Admin', 'Admin']))
                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline-block" data-confirm-message="Are you sure you want to delete this role?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light rounded-circle" title="Delete">
                                            <i class="bi bi-trash text-danger text-danger"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-lock fs-1 d-block mb-3 opacity-50"></i>
                                No custom roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-top">
            {{ $roles->links() }}
        </div>
    </div>
</div>
@endsection
