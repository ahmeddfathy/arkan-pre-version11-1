<?php

namespace App\Services\ProjectDashboard;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DateFilterService
{
    /**
     * معالجة فلاتر التاريخ من الطلب
     */
    public function processDateFilters(Request $request)
    {
        $filterType = $request->get('filter_type', 'quick');
        $fromDate = null;
        $toDate = null;
        $period = null;

        if ($filterType == 'quick') {
            $quickPeriod = $request->get('quick_period');
            if ($quickPeriod) {
                $dates = $this->getQuickPeriodDates($quickPeriod);
                $fromDate = $dates['from'];
                $toDate = $dates['to'];
                $period = $quickPeriod;
            }
        } else if ($filterType == 'custom') {
            $fromDateInput = $request->get('from_date');
            $toDateInput = $request->get('to_date');

            if ($fromDateInput) {
                $fromDate = Carbon::parse($fromDateInput)->startOfDay();
            }
            if ($toDateInput) {
                $toDate = Carbon::parse($toDateInput)->endOfDay();
            }
        }

        return [
            'filter_type' => $filterType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'period' => $period,
            'has_filter' => $fromDate || $toDate
        ];
    }

    /**
     * الحصول على تواريخ الفترات السريعة
     */
    protected function getQuickPeriodDates($period)
    {
        switch ($period) {
            case 'this_month':
                return [
                    'from' => Carbon::now()->startOfMonth(),
                    'to' => Carbon::now()->endOfMonth()
                ];

            case 'last_month':
                return [
                    'from' => Carbon::now()->subMonth()->startOfMonth(),
                    'to' => Carbon::now()->subMonth()->endOfMonth()
                ];

            case 'this_quarter':
                return [
                    'from' => Carbon::now()->startOfQuarter(),
                    'to' => Carbon::now()->endOfQuarter()
                ];

            case 'last_quarter':
                return [
                    'from' => Carbon::now()->subQuarter()->startOfQuarter(),
                    'to' => Carbon::now()->subQuarter()->endOfQuarter()
                ];

            case 'this_year':
                return [
                    'from' => Carbon::now()->startOfYear(),
                    'to' => Carbon::now()->endOfYear()
                ];

            case 'last_year':
                return [
                    'from' => Carbon::now()->subYear()->startOfYear(),
                    'to' => Carbon::now()->subYear()->endOfYear()
                ];

            case 'last_7_days':
                return [
                    'from' => Carbon::now()->subDays(7)->startOfDay(),
                    'to' => Carbon::now()->endOfDay()
                ];

            case 'last_30_days':
                return [
                    'from' => Carbon::now()->subDays(30)->startOfDay(),
                    'to' => Carbon::now()->endOfDay()
                ];

            default:
                return ['from' => null, 'to' => null];
        }
    }

    /**
     * تطبيق فلترة التاريخ على query
     */
    public function applyDateFilter($query, $dateField, $fromDate = null, $toDate = null)
    {
        if ($fromDate) {
            $query->where($dateField, '>=', $fromDate);
        }

        if ($toDate) {
            $query->where($dateField, '<=', $toDate);
        }

        return $query;
    }

    /**
     * تطبيق فلترة التاريخ على query بتاريخ الإنشاء
     */
    public function applyCreatedAtFilter($query, $fromDate = null, $toDate = null)
    {
        return $this->applyDateFilter($query, 'created_at', $fromDate, $toDate);
    }

    /**
     * تطبيق فلترة التاريخ على query بتاريخ التحديث
     */
    public function applyUpdatedAtFilter($query, $fromDate = null, $toDate = null)
    {
        return $this->applyDateFilter($query, 'updated_at', $fromDate, $toDate);
    }

    /**
     * الحصول على تواريخ افتراضية إذا لم تكن محددة
     */
    public function getDefaultDateRange()
    {
        return [
            'from' => Carbon::now()->startOfMonth(),
            'to' => Carbon::now()->endOfMonth()
        ];
    }

    /**
     * تنسيق التواريخ للعرض
     */
    public function formatDateForDisplay($date)
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    /**
     * الحصول على نص وصف الفترة
     */
    public function getPeriodDescription($filterData)
    {
        if ($filterData['period']) {
            return $this->getQuickPeriodLabel($filterData['period']);
        } elseif ($filterData['from_date'] && $filterData['to_date']) {
            return 'من ' . $this->formatDateForDisplay($filterData['from_date']) .
                   ' إلى ' . $this->formatDateForDisplay($filterData['to_date']);
        } elseif ($filterData['from_date']) {
            return 'من ' . $this->formatDateForDisplay($filterData['from_date']);
        } elseif ($filterData['to_date']) {
            return 'حتى ' . $this->formatDateForDisplay($filterData['to_date']);
        }

        return 'جميع الفترات';
    }

    /**
     * الحصول على تسمية الفترة السريعة
     */
    protected function getQuickPeriodLabel($period)
    {
        $labels = [
            'this_month' => 'الشهر الحالي',
            'last_month' => 'الشهر الماضي',
            'this_quarter' => 'الربع الحالي',
            'last_quarter' => 'الربع الماضي',
            'this_year' => 'السنة الحالية',
            'last_year' => 'السنة الماضية',
            'last_7_days' => 'آخر 7 أيام',
            'last_30_days' => 'آخر 30 يوم'
        ];

        return $labels[$period] ?? 'فترة غير معروفة';
    }
}
