<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return view('crm.users.index');
    }

    public function create()
    {
        $permissionMatrix = $this->assignablePermissionMatrix();
        $permissionActions = config('crm_permissions.actions', []);
        $roles = Role::all();

        return view('crm.users.create', compact('permissionMatrix', 'permissionActions', 'roles'));
    }

    public function show(User $user)
    {
        $user->load('roles.permissions');

        $totalEarned = (float) \App\Models\CommissionEntry::where('user_id', $user->id)
            ->sum('commission_amount');

        $totalWithdrawnApproved = (float) \App\Models\WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $pendingWithdrawalAmount = (float) \App\Models\WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        $availableBalance = max(0, $totalEarned - $totalWithdrawnApproved - $pendingWithdrawalAmount);

        $commissionEntries = \App\Models\CommissionEntry::with(['policy', 'commissionRule'])
            ->where('user_id', $user->id)
            ->latest('entry_date')
            ->get();

        return view('crm.users.show', compact(
            'user',
            'totalEarned',
            'totalWithdrawnApproved',
            'pendingWithdrawalAmount',
            'availableBalance',
            'commissionEntries'
        ));
    }

    public function image(User $user)
    {
        // Check authentication - accept both session and token
        if (!auth()->check()) {
            abort(403);
        }

        // Check authorization - user can only see their own image or admin can see all
        if (auth()->id() !== $user->id && !auth()->user()->isMainAdmin()) {
            abort(403);
        }

        // If no avatar, return default
        if (!$user->avatar_path) {
            // Return a default avatar URL instead of 404
            return redirect('https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=ffffff&size=128');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($user->avatar_path)) {
            // Return default avatar instead of 404
            return redirect('https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=ffffff&size=128');
        }

        $fullPath = Storage::disk('public')->path($user->avatar_path);

        return Response::file($fullPath);
    }

    public function edit(User $user)
    {
        $permissionMatrix = $this->assignablePermissionMatrix();
        $permissionActions = config('crm_permissions.actions', []);
        $userPermissions = $user->permissions->pluck('name')->all();
        $roles = Role::all();

        return view('crm.users.edit', compact('user', 'permissionMatrix', 'permissionActions', 'userPermissions', 'roles'));
    }

    private function assignablePermissionMatrix(): array
    {
        return collect(config('crm_permissions.modules', []))
            ->except(['users', 'roles'])
            ->all();
    }

    public function export(Request $request)
    {
        $fileName = 'users_' . date('Y-m-d_H-i-s') . '.csv';
        $query = User::with('roles')
            ->latest()
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($q) use ($request) {
                $q->whereHas('roles', fn($role) => $role->where('name', $request->role));
            })
            ->when($request->from_date && $request->to_date, function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59',
                ]);
            });

        $users = $query->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Sr no.', 'Name', 'Email', 'Contact', 'Address', 'Role', 'Status'];

        $callback = function () use ($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $i = 1;
            foreach ($users as $user) {
                $contact = $user->phone ? '="' . preg_replace('/\D+/', '', $user->phone) . '"' : '';
                $role = $user->roles->pluck('name')->map(fn($name) => ucfirst($name))->implode('|') ?: 'Staff';

                fputcsv($file, [
                    $i++,
                    $user->name,
                    $user->email,
                    $contact,
                    $user->address,
                    $role,
                    ($user->is_active ?? true) ? 'Active' : 'Inactive',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv'],
        ]);

        $path = $request->file('import_file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return redirect()->route('users.index')->with('error', 'Unable to read the import file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return redirect()->route('users.index')->with('error', 'The CSV file is empty or missing headers.');
        }

        $headerMap = collect($header)->map(function ($value) {
            return strtolower(trim((string) $value));
        })->values()->all();
        
        $newCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue; // Skip empty rows

            $rowData = [];
            foreach ($headerMap as $index => $column) {
                if (isset($row[$index])) {
                    $rowData[$column] = trim((string) $row[$index]);
                }
            }

            $name = $rowData['name'] ?? '';
            $email = $rowData['email'] ?? '';
            
            if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                continue;
            }

            $phone = $rowData['contact'] ?? ($rowData['phone'] ?? '');
            $address = $rowData['address'] ?? '';
            $rolesRaw = $rowData['role'] ?? ($rowData['roles'] ?? '');
            $password = (string) ($rowData['password'] ?? '');

            $phone = preg_replace('/[^0-9]/', '', $phone);
            if ($phone !== '' && strlen($phone) > 10) {
                $phone = substr($phone, -10);
            }

            $user = User::where('email', $email)->first();
            $isNew = false;
            
            if (!$user) {
                $user = new User();
                $user->email = $email;
                $user->password = Hash::make($password !== '' ? $password : 'Password@123');
                $isNew = true;
            } elseif ($password !== '') {
                $user->password = Hash::make($password);
            }

            $user->name = $name;
            $user->phone = $phone ?: $user->phone;
            $user->address = $address ?: $user->address;
            $user->save();

            if ($rolesRaw !== '') {
                $rolesArr = collect(preg_split('/[|,]/', $rolesRaw))
                    ->map(fn($role) => trim($role))
                    ->filter()
                    ->map(fn($role) => strtolower($role))
                    ->unique()
                    ->values()
                    ->all();

                $dbRoles = Role::whereIn('name', $rolesArr)->pluck('name')->all();

                if (!empty($dbRoles)) {
                    $user->syncRoles($dbRoles);
                }
            } elseif (!$user->roles()->exists() && $staffRole = Role::where('name', 'staff')->first()) {
                $user->syncRoles([$staffRole->name]);
            }

            if ($isNew) $newCount++; else $updatedCount++;
        }

        fclose($handle);

        $totalImported = $newCount + $updatedCount;
        if ($totalImported > 0) {
            $msg = "{$totalImported} staff(s) processed successfully. ";
            $msg .= "({$newCount} new added" . ($updatedCount > 0 ? ", {$updatedCount} updated" : "") . ").";
            if ($skippedCount > 0) $msg .= " {$skippedCount} row(s) skipped.";
            return redirect()->route('users.index')->with('success', $msg);
        }

        return redirect()->route('users.index')->with('warning', 'No valid staff data found to import.');
    }

    public function apiSearch(Request $request)
    {
        $search = $request->get('q');

        $users = User::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
