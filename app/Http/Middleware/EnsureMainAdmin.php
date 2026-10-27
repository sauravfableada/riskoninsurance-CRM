<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMainAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user->is_active ?? true)) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $controllerMethod = (string) ($request->route()?->getActionMethod() ?? '');
        $controllerClass = (string) ($request->route()?->getControllerClass() ?? '');
        $module = str_contains($controllerClass, 'RoleController') ? 'roles' : 'users';

        $action = match ($controllerMethod) {
            'index', 'show', 'search', 'export', 'image' => 'view',
            'create', 'store', 'import' => 'create',
            'edit', 'update', 'updateStatus', 'updatePermissions' => 'edit',
            'destroy', 'delete' => 'delete',
            default => null,
        };

        $canManageStaff = $action !== null
            && $user->hasMatrixPermission("{$action}_{$module}");

        if (!$canManageStaff) {
            abort(403);
        }

        return $next($request);
    }
}
