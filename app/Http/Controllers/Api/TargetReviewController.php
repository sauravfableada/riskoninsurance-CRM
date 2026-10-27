<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesTargetReview;
use App\Http\Controllers\Controller;
use App\Models\TargetReviewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TargetReviewController extends Controller
{
    use HandlesTargetReview;

    public function list(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $targets = $this->targetReviewBuildListQuery(
            $user,
            $request->query('from_month'),
            $request->query('to_month')
        )
            ->orderByDesc('id')
            ->get();

        $departmentMap = $this->targetReviewDepartmentMap();
        $factorMap = $this->targetReviewFactorMap();

        return response()->json([
            'status' => true,
            'data' => $targets->values()->map(function (TargetReviewModel $target) use ($departmentMap, $factorMap) {
                $departmentId = (int) ($target->department ?? 0);
                $factorId = (int) ($target->factor ?? 0);

                return [
                    'id' => $target->id,
                    'transaction_date' => optional($target->transaction_date)->format('Y-m-d') ?: '--',
                    'transaction_num' => $target->transaction_number ?: '--',
                    'target_month' => $target->target_month ?: '--',
                    'target_type' => $target->target_type ?: '--',
                    'department' => $departmentMap[$departmentId] ?? 'Unknown',
                    'department_id' => $departmentId > 0 ? $departmentId : null,
                    'factor' => $factorMap[$factorId] ?? 'Unknown',
                    'factor_id' => $factorId > 0 ? $factorId : null,
                    'target_amount' => (float) ($target->target_amount ?? 0),
                    'achieved_amount' => (float) ($target->achieved_amount ?? 0),
                    'reason' => $target->reason ?? '',
                ];
            })->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'transaction_date' => ['required', 'date'],
            'transaction_number' => ['required', 'string', 'max:255'],
            'target_type' => ['required', 'in:Business Target,Commission Income Target'],
            'target_month' => ['required', 'regex:/^\d{2}-\d{4}$/'],
            'department_id' => ['required', 'integer', 'in:1,2,3'],
            'factor_id' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'achieved_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ], [
            'target_month.regex' => 'Target Month format must be mm-yyyy.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $target = TargetReviewModel::create([
            'transaction_date' => $validated['transaction_date'],
            'transaction_number' => trim((string) $validated['transaction_number']),
            'target_type' => $validated['target_type'],
            'target_month' => $validated['target_month'],
            'department' => (int) $validated['department_id'],
            'factor' => (int) $validated['factor_id'],
            'target_amount' => (float) $validated['target_amount'],
            'achieved_amount' => (float) ($validated['achieved_amount'] ?? 0),
            'reason' => trim((string) ($validated['reason'] ?? '')),
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Target added successfully',
            'data' => ['id' => $target->id],
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $target = TargetReviewModel::find($id);
        if (!$target) {
            return response()->json(['status' => false, 'message' => 'Target not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $target->id,
                'transaction_date' => optional($target->transaction_date)->format('Y-m-d') ?: '',
                'transaction_number' => $target->transaction_number ?? '',
                'target_type' => $target->target_type ?? '',
                'target_month' => $target->target_month ?? '',
                'department_id' => $target->department,
                'factor_id' => $target->factor,
                'target_amount' => (float) ($target->target_amount ?? 0),
                'achieved_amount' => (float) ($target->achieved_amount ?? 0),
                'reason' => $target->reason ?? '',
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $target = TargetReviewModel::find($id);
        if (!$target) {
            return response()->json(['status' => false, 'message' => 'Target not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'transaction_date' => ['required', 'date'],
            'transaction_number' => ['required', 'string', 'max:255'],
            'target_type' => ['required', 'in:Business Target,Commission Income Target'],
            'target_month' => ['required', 'regex:/^\d{2}-\d{4}$/'],
            'department_id' => ['required', 'integer', 'in:1,2,3'],
            'factor_id' => ['required', 'integer', 'in:1,2,3,4,5,6'],
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'achieved_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ], [
            'target_month.regex' => 'Target Month format must be mm-yyyy.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $target->update([
            'transaction_date' => $validated['transaction_date'],
            'transaction_number' => trim((string) $validated['transaction_number']),
            'target_type' => $validated['target_type'],
            'target_month' => $validated['target_month'],
            'department' => (int) $validated['department_id'],
            'factor' => (int) $validated['factor_id'],
            'target_amount' => (float) $validated['target_amount'],
            'achieved_amount' => (float) ($validated['achieved_amount'] ?? $target->achieved_amount),
            'reason' => trim((string) ($validated['reason'] ?? '')),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Target updated successfully',
        ]);
    }

    public function view(int $id): JsonResponse
    {
        $target = TargetReviewModel::find($id);
        if (!$target) {
            return response()->json(['status' => false, 'message' => 'Target not found'], 404);
        }

        $departmentMap = $this->targetReviewDepartmentMap();
        $factorMap = $this->targetReviewFactorMap();
        $departmentId = (int) ($target->department ?? 0);
        $factorId = (int) ($target->factor ?? 0);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $target->id,
                'transaction_date' => optional($target->transaction_date)->format('d-m-Y') ?: '--',
                'transaction_number' => $target->transaction_number ?: '--',
                'target_type' => $target->target_type ?: '--',
                'target_month' => $target->target_month ?: '--',
                'department' => $departmentMap[$departmentId] ?? 'Unknown',
                'department_id' => $departmentId > 0 ? $departmentId : null,
                'factor' => $factorMap[$factorId] ?? 'Unknown',
                'factor_id' => $factorId > 0 ? $factorId : null,
                'target_amount' => (float) ($target->target_amount ?? 0),
                'achieved_amount' => (float) ($target->achieved_amount ?? 0),
                'reason' => $target->reason ?: '--',
            ],
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $target = TargetReviewModel::find($id);
        if (!$target) {
            return response()->json(['status' => false, 'message' => 'Target not found'], 404);
        }

        $target->delete();

        return response()->json([
            'status' => true,
            'message' => 'Target deleted successfully',
        ]);
    }

    public function getReportData(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $reportType = in_array($request->query('report_type'), ['business', 'commission'], true)
            ? $request->query('report_type')
            : 'business';

        $yearType = in_array($request->query('year_type'), ['financial', 'calendar'], true)
            ? $request->query('year_type')
            : 'financial';

        $selectedMonth = $request->query('month');

        $context = $this->targetReviewReportContext($selectedMonth);
        $targets = $this->targetReviewBuildReportQuery($user, $reportType, $selectedMonth)
            ->orderBy('target_month')
            ->orderBy('department')
            ->orderBy('factor')
            ->get()
            ->toArray();

        $reportData = $this->targetReviewBuildReportData(
            $targets,
            $context['financial_years'],
            $yearType,
            (int) $context['current_year'],
            $selectedMonth
        );

        return response()->json([
            'status' => true,
            'data' => $reportData,
            'financial_years' => $context['financial_years'],
            'report_type' => $reportType,
            'year_type' => $yearType,
        ]);
    }
}
