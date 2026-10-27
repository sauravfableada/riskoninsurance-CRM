<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesTargetReview;
use App\Models\TargetReviewModel;
use App\Models\TargetDepartment;
use App\Models\TargetFactor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TargetReviewController extends Controller
{
    use HandlesTargetReview;

    public function index(): View
    {
        return view('targetandreview.index');
    }

    public function report(): View
    {
        return view('targetandreview.report');
    }

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

        $data = $targets->values()->map(function (TargetReviewModel $target, int $index) use ($departmentMap, $factorMap) {
            $departmentId = (int) ($target->department ?? 0);
            $factorId = (int) ($target->factor ?? 0);

            return [
                'id' => $target->id,
                'sr_no' => $index + 1,
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
        })->all();

        return response()->json([
            'status' => true,
            'data' => $data,
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
            'target_month' => ['required', 'date_format:Y-m'],
            'department_id' => ['required', 'integer', 'exists:target_departments,id'],
            'factor_id' => ['required', 'integer', Rule::exists('target_factors', 'id')->where('target_department_id', $request->input('department_id'))],
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'achieved_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ], [
            'target_month.date_format' => 'Please select a valid target month and year.',
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
            'target_month' => date('m-Y', strtotime($validated['target_month'] . '-01')),
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
                'target_month' => preg_match('/^(\d{2})-(\d{4})$/', (string) $target->target_month, $monthParts)
                    ? $monthParts[2] . '-' . $monthParts[1]
                    : '',
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
            'target_month' => ['required', 'date_format:Y-m'],
            'department_id' => ['required', 'integer', 'exists:target_departments,id'],
            'factor_id' => ['required', 'integer', Rule::exists('target_factors', 'id')->where('target_department_id', $request->input('department_id'))],
            'target_amount' => ['required', 'numeric', 'gt:0'],
            'achieved_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ], [
            'target_month.date_format' => 'Please select a valid target month and year.',
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
            'target_month' => date('m-Y', strtotime($validated['target_month'] . '-01')),
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

    public function departments(): JsonResponse
    {
        $departments = collect($this->targetReviewDepartmentMap())
            ->map(fn (string $name, int $id) => ['id' => $id, 'name' => $name])
            ->values();

        return response()->json([
            'status' => true,
            'data' => $departments,
        ]);
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:target_departments,name'],
        ]);

        $department = TargetDepartment::create(['name' => trim($validated['name'])]);

        return response()->json(['status' => true, 'message' => 'Department added successfully', 'data' => $department]);
    }

    public function storeFactor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:target_departments,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $factor = TargetFactor::firstOrCreate([
            'target_department_id' => $validated['department_id'],
            'name' => trim($validated['name']),
        ]);

        return response()->json(['status' => true, 'message' => 'Factor added successfully', 'data' => $factor]);
    }

    public function factors(Request $request): JsonResponse
    {
        $departmentId = (int) $request->query('department_id', 0);
        $departmentFactors = $this->targetReviewDepartmentFactors();
        $factorMap = $this->targetReviewFactorMap();

        $allowed = $departmentId > 0 ? ($departmentFactors[$departmentId] ?? []) : array_keys($factorMap);

        $factors = collect($allowed)
            ->map(fn (int $id) => ['id' => $id, 'name' => $factorMap[$id] ?? 'Unknown'])
            ->values();

        return response()->json([
            'status' => true,
            'data' => $factors,
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

    public function downloadPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401, 'Unauthorized');
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

        $data = [
            'report_type' => $reportType === 'business' ? 'Business Review Report' : 'Commission Review Report',
            'year_type' => $yearType === 'financial' ? 'Financial Year' : 'Calendar Year',
            'report_data' => $reportData,
            'financial_years' => $context['financial_years'],
            'current_date' => now()->format('d/m/y h:i A'),
            'user_name' => $user->name ?? '--',
        ];

        $pdf = Pdf::loadView('targetandreview.pdf_report', $data)->setPaper('a4', 'landscape');

        return $pdf->download('target_review_report_' . now()->format('YmdHis') . '.pdf');
    }
}
