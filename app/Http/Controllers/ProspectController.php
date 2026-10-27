<?php

namespace App\Http\Controllers;

use App\Models\ProspectModel;
use App\Models\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProspectController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $query = ProspectModel::query();

        if ($this->shouldRestrictToOwnProspects($user)) {
            $query->where('created_by', $user->id);
        }

        $prospects = $query->get();

        $permission = UserPermission::query()
            ->where('user_id', $user->id)
            ->where('module_id', 15)
            ->first();

        $users = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('prospect.index', [
            'prospects' => $prospects,
            'permission' => $permission,
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('prospect.create');
    }

    public function store(Request $request): JsonResponse
    {
        $contact = $this->internationalMobile($request);
        $prospect = ProspectModel::create([
            'name' => trim((string) $request->input('name')),
            'mobile_no' => $contact['mobile_no'],
            'country_code' => $contact['country_code'],
            'area' => trim((string) $request->input('area')),
            'created_by' => auth()->id(),
            'status' => $request->input('status'),
        ]);

        if ($prospect) {
            return response()->json([
                'status' => 'success',
                'message' => 'Prospect added successfully.',
                'insert_id' => $prospect->id,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to save prospect.',
        ], 500);
    }

    public function edit(int $id): View|RedirectResponse
    {
        $prospect = ProspectModel::find($id);

        if (!$prospect) {
            return redirect()->route('prospects.index')
                ->with('error', 'Prospect not found.');
        }

        return view('prospect.edit', [
            'prospect' => $prospect,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');

        if ($id <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Prospect ID.',
            ]);
        }

        $status = $request->input('status', 'prospect');
        $validStatuses = ['prospect', 'client'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'prospect';
        }

        try {
            $contact = $this->internationalMobile($request);
            $updated = ProspectModel::query()->whereKey($id)->update([
                'name' => trim((string) $request->input('name')),
                'mobile_no' => $contact['mobile_no'],
                'country_code' => $contact['country_code'],
                'area' => trim((string) $request->input('area')),
                'status' => $status,
            ]);

            if ($updated) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Prospect updated successfully.',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update prospect.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        if ($id <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Prospect ID.',
            ]);
        }

        try {
            ProspectModel::query()->whereKey($id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $id = trim((string) $request->input('id'));
        $status = trim((string) $request->input('status'));

        if ($id === '' || !is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing Prospect ID.',
            ]);
        }

        $validStatuses = ['prospect', 'client'];
        if (!in_array($status, $validStatuses, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status value.',
            ]);
        }

        try {
            $prospect = ProspectModel::find((int) $id);
            if (!$prospect) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prospect not found.',
                ]);
            }

            $prospect->update(['status' => $status]);

            return response()->json([
                'status' => 'success',
                'message' => 'Prospect status updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function shouldRestrictToOwnProspects(object $user): bool
    {
        $legacyRole = data_get($user, 'role');

        if ($legacyRole !== null) {
            return (string) $legacyRole === '1';
        }

        if (method_exists($user, 'isAdmin')) {
            return !$user->isAdmin();
        }

        return true;
    }

    private function internationalMobile(Request $request): array
    {
        $countryCode = (string) $request->input('country_code', '+91');
        if (!preg_match('/^\+[0-9]{1,4}$/', $countryCode)) {
            $countryCode = '+91';
        }
        $mobile = preg_replace('/\D+/', '', (string) $request->input('mobile_no', ''));

        return [
            'country_code' => $countryCode,
            'mobile_no' => $mobile !== '' ? $countryCode . $mobile : '',
        ];
    }
}
