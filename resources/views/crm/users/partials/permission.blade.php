@php
    $selectedPermissions = $selectedPermissions ?? old('permissions', []);
@endphp

<div class="mt-5">
    <div class="permission-section-title">Permission</div>

    <label class="form-label d-flex align-items-center gap-2 mb-3">
        <input type="checkbox" class="form-check-input permission-select-all" id="selectAllPermissions">
        <span>Select All Module</span>
    </label>

    <div class="permission-matrix-card" data-permission-matrix>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Module Name</th>
                        <th class="text-center">View</th>
                        <th class="text-center">Insert</th>
                        <th class="text-center">Edit</th>
                        <th class="text-center">Delete</th>
                        <th class="text-center">All</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissionMatrix as $module => $meta)
                        @php
                            $moduleActions = $meta['actions'] ?? array_keys($permissionActions);
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="module-icon"><i class="bi {{ $meta['icon'] }}"></i></span>
                                    <span>{{ $meta['label'] }}</span>
                                </div>
                            </td>
                            @foreach(array_keys($permissionActions) as $action)
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        class="form-check-input permission-checkbox module-action-toggle"
                                        name="permissions[]"
                                        value="{{ $action }}_{{ $module }}"
                                        {{ in_array($action . '_' . $module, $selectedPermissions, true) ? 'checked' : '' }}
                                        {{ in_array($action, $moduleActions, true) ? '' : 'disabled' }}
                                    >
                                </td>
                            @endforeach
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input permission-checkbox module-all-toggle" {{ count($moduleActions) > 1 ? '' : 'disabled' }}>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @error('permissions')
        <div class="staff-validation">{{ $message }}</div>
    @enderror
</div>
