<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends ApiBaseController
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return $this->success($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        
        if (!empty($validated['permissions'])) {
            $role->givePermissionTo($validated['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role created successfully', 201);
    }

    public function show(Role $role)
    {
        return $this->success($role->load('permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $validated['name']]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($validated['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role updated successfully');
    }
    public function destroy(Role $role)
    {
        if ($role->name === 'Admin' || $role->name === 'Super Admin') {
            return $this->error('Cannot delete a core system role.', 422);
        }

        $role->delete();

        return $this->success(null, 'Role deleted successfully');
    }
    public function permissions()
    {
        return $this->success(Permission::all());
    }
}

