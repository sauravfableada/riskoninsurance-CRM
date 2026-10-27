<?php

namespace App\Http\Controllers\Concerns;

use App\Models\TargetReviewModel;
use App\Models\TargetDepartment;
use App\Models\TargetFactor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HandlesTargetReview
{
    protected function targetReviewDepartmentMap(): array
    {
        return TargetDepartment::query()->orderBy('id')->pluck('name', 'id')->all();
    }

    protected function targetReviewFactorMap(): array
    {
        return TargetFactor::query()->orderBy('id')->pluck('name', 'id')->all();
    }

    protected function targetReviewDepartmentFactors(): array
    {
        return TargetFactor::query()
            ->orderBy('id')
            ->get(['id', 'target_department_id'])
            ->groupBy('target_department_id')
            ->map(fn ($factors) => $factors->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->all();
    }

    protected function targetReviewShouldRestrictToUser(?User $user): bool
    {
        if (!$user) {
            return true;
        }

        $legacyRole = data_get($user, 'role');
        if ($legacyRole !== null) {
            return (string) $legacyRole !== '3';
        }

        if (method_exists($user, 'isAdmin')) {
            return !$user->isAdmin();
        }

        return true;
    }

    protected function targetReviewBuildListQuery(User $user, ?string $fromMonth, ?string $toMonth): Builder
    {
        $query = TargetReviewModel::query();

        if ($this->targetReviewShouldRestrictToUser($user)) {
            $query->where('user_id', $user->id);
        }

        if (!empty($fromMonth)) {
            try {
                $fromDate = Carbon::parse($fromMonth)->startOfMonth()->format('Y-m-d');
                $query->whereRaw("STR_TO_DATE(CONCAT('01-', target_month), '%d-%m-%Y') >= ?", [$fromDate]);
            } catch (\Throwable $e) {
                // Ignore invalid filter value
            }
        }

        if (!empty($toMonth)) {
            try {
                $toDate = Carbon::parse($toMonth)->endOfMonth()->format('Y-m-d');
                $query->whereRaw("STR_TO_DATE(CONCAT('01-', target_month), '%d-%m-%Y') <= ?", [$toDate]);
            } catch (\Throwable $e) {
                // Ignore invalid filter value
            }
        }

        return $query;
    }

    protected function targetReviewBuildReportQuery(User $user, string $reportType, ?string $selectedMonth): Builder
    {
        $query = TargetReviewModel::query();

        if ($this->targetReviewShouldRestrictToUser($user)) {
            $query->where('user_id', $user->id);
        }

        if ($reportType === 'business') {
            $query->where('target_type', 'Business Target');
        } elseif ($reportType === 'commission') {
            $query->where('target_type', 'Commission Income Target');
        }

        if (!empty($selectedMonth)) {
            $query->where('target_month', $selectedMonth);
        }

        return $query;
    }

    protected function targetReviewReportContext(?string $selectedMonth): array
    {
        if (!empty($selectedMonth) && preg_match('/^(\d{2})-(\d{4})$/', $selectedMonth, $matches)) {
            $currentMonth = (int) $matches[1];
            $currentYear = (int) $matches[2];
        } else {
            $now = now();
            $currentMonth = (int) $now->format('m');
            $currentYear = (int) $now->format('Y');
        }

        if ($currentMonth >= 4) {
            $currentFYStart = $currentYear;
            $currentFYEnd = $currentYear + 1;
        } else {
            $currentFYStart = $currentYear - 1;
            $currentFYEnd = $currentYear;
        }

        return [
            'current_month' => $currentMonth,
            'current_year' => $currentYear,
            'financial_years' => [
                'current' => [
                    'start' => $currentFYStart,
                    'end' => $currentFYEnd,
                    'label' => $currentFYStart . '-' . substr((string) $currentFYEnd, -2),
                ],
                'next' => [
                    'start' => $currentFYStart + 1,
                    'end' => $currentFYEnd + 1,
                    'label' => ($currentFYStart + 1) . '-' . substr((string) ($currentFYEnd + 1), -2),
                ],
                'next_to_next' => [
                    'start' => $currentFYStart + 2,
                    'end' => $currentFYEnd + 2,
                    'label' => ($currentFYStart + 2) . '-' . substr((string) ($currentFYEnd + 2), -2),
                ],
            ],
        ];
    }

    protected function targetReviewBuildReportData(array $targets, array $financialYears, string $yearType, int $baseYear, ?string $selectedMonth): array
    {
        $departmentMap = $this->targetReviewDepartmentMap();
        $factorMap = $this->targetReviewFactorMap();
        $departmentFactors = $this->targetReviewDepartmentFactors();

        $reportData = [];

        if (!empty($selectedMonth) && preg_match('/^(\d{2})-(\d{4})$/', $selectedMonth)) {
            $this->targetReviewInitializeMonthData($reportData, $selectedMonth, $departmentMap, $factorMap, $departmentFactors);
        }

        foreach ($targets as $target) {
            $targetMonth = (string) ($target['target_month'] ?? '');
            $departmentId = isset($target['department']) ? (int) $target['department'] : 0;
            $factorId = isset($target['factor']) ? (int) $target['factor'] : 0;

            if ($targetMonth === '' || $departmentId <= 0 || $factorId <= 0 || !preg_match('/^(\d{2})-(\d{4})$/', $targetMonth, $matches)) {
                continue;
            }

            $monthNum = (int) $matches[1];
            $year = (int) $matches[2];
            $yearSlot = $this->targetReviewResolveYearSlot($monthNum, $year, $financialYears, $yearType, $baseYear);

            if (!isset($reportData[$targetMonth])) {
                $this->targetReviewInitializeMonthData($reportData, $targetMonth, $departmentMap, $factorMap, $departmentFactors);
            }

            if (!isset($reportData[$targetMonth]['departments'][$departmentId])) {
                continue;
            }

            if (!isset($reportData[$targetMonth]['departments'][$departmentId]['factors'][$factorId])) {
                continue;
            }

            $targetAmount = (float) ($target['target_amount'] ?? 0);
            $achievedAmount = (float) ($target['achieved_amount'] ?? 0);
            $difference = $achievedAmount - $targetAmount;
            $reason = (string) ($target['reason'] ?? '');

            if ($yearSlot !== null) {
                $reportData[$targetMonth]['departments'][$departmentId]['factors'][$factorId][$yearSlot] = [
                    'target' => $targetAmount,
                    'achieved' => $achievedAmount,
                    'difference' => $difference,
                    'reason' => $reason,
                ];
            }
        }

        foreach ($reportData as $monthKey => $monthData) {
            $totals = [
                'current_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0],
                'next_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0],
                'next_to_next_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0],
            ];

            foreach ($monthData['departments'] as $department) {
                foreach ($department['factors'] as $factor) {
                    foreach (['current_year', 'next_year', 'next_to_next_year'] as $slot) {
                        $totals[$slot]['target'] += (float) $factor[$slot]['target'];
                        $totals[$slot]['achieved'] += (float) $factor[$slot]['achieved'];
                        $totals[$slot]['difference'] += (float) $factor[$slot]['difference'];
                    }
                }
            }

            $reportData[$monthKey]['monthly_total'] = $totals;
        }

        uksort($reportData, function (string $a, string $b): int {
            return $this->targetReviewMonthSortKey($a) <=> $this->targetReviewMonthSortKey($b);
        });

        $formatted = [];
        $departmentOrder = [3, 1, 2];
        foreach ($reportData as $monthData) {
            $month = [
                'month' => $monthData['month'],
                'month_value' => $monthData['month_value'],
                'departments' => [],
                'monthly_total' => $monthData['monthly_total'],
            ];

            foreach ($departmentOrder as $departmentId) {
                if (!isset($monthData['departments'][$departmentId])) {
                    continue;
                }

                $department = $monthData['departments'][$departmentId];
                $factors = [];
                foreach ($departmentFactors[$departmentId] as $factorId) {
                    if (isset($department['factors'][$factorId])) {
                        $factors[] = $department['factors'][$factorId];
                    }
                }

                $month['departments'][] = [
                    'department_id' => $department['department_id'],
                    'department_name' => $department['department_name'],
                    'factors' => $factors,
                ];
            }

            $formatted[] = $month;
        }

        return $formatted;
    }

    protected function targetReviewResolveYearSlot(int $monthNum, int $year, array $financialYears, string $yearType, int $baseYear): ?string
    {
        if ($yearType === 'financial') {
            $targetFYStart = $monthNum >= 4 ? $year : ($year - 1);

            if ($targetFYStart === (int) $financialYears['current']['start']) {
                return 'current_year';
            }
            if ($targetFYStart === (int) $financialYears['next']['start']) {
                return 'next_year';
            }
            if ($targetFYStart === (int) $financialYears['next_to_next']['start']) {
                return 'next_to_next_year';
            }

            return null;
        }

        if ($year === $baseYear) {
            return 'current_year';
        }
        if ($year === ($baseYear + 1)) {
            return 'next_year';
        }
        if ($year === ($baseYear + 2)) {
            return 'next_to_next_year';
        }

        return null;
    }

    protected function targetReviewInitializeMonthData(array &$reportData, string $monthValue, array $departmentMap, array $factorMap, array $departmentFactors): void
    {
        $monthNum = (int) substr($monthValue, 0, 2);
        $monthLabel = Carbon::createFromDate(2000, max(1, min(12, $monthNum)), 1)->format('F');

        $reportData[$monthValue] = [
            'month' => $monthLabel,
            'month_value' => $monthValue,
            'departments' => [],
            'monthly_total' => [
                'current_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0],
                'next_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0],
                'next_to_next_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0],
            ],
        ];

        foreach ($departmentFactors as $departmentId => $factorIds) {
            $reportData[$monthValue]['departments'][$departmentId] = [
                'department_id' => $departmentId,
                'department_name' => $departmentMap[$departmentId] ?? 'Unknown',
                'factors' => [],
            ];

            foreach ($factorIds as $factorId) {
                $reportData[$monthValue]['departments'][$departmentId]['factors'][$factorId] = [
                    'factor_id' => $factorId,
                    'factor_name' => $factorMap[$factorId] ?? 'Unknown',
                    'current_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0, 'reason' => ''],
                    'next_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0, 'reason' => ''],
                    'next_to_next_year' => ['target' => 0, 'achieved' => 0, 'difference' => 0, 'reason' => ''],
                ];
            }
        }
    }

    protected function targetReviewMonthSortKey(string $monthValue): int
    {
        if (!preg_match('/^(\d{2})-(\d{4})$/', $monthValue, $matches)) {
            return 0;
        }

        $month = (int) $matches[1];
        $year = (int) $matches[2];

        return ($year * 100) + $month;
    }
}
