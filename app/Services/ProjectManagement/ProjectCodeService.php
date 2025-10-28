<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use Illuminate\Support\Facades\Log;

class ProjectCodeService
{
    /**
     * التحقق من توفر كود المشروع
     */
    public function checkCodeAvailability(string $code): array
    {
        try {
            if (!$code) {
                return [
                    'success' => false,
                    'message' => 'الكود مطلوب',
                    'status_code' => 400
                ];
            }

            // التحقق من أن الكود غير مستخدم في مشاريع غير محذوفة
            $existingProject = Project::where('code', $code)
                ->whereNull('deleted_at')
                ->first();

            return [
                'success' => true,
                'available' => !$existingProject,
                'message' => $existingProject ? 'الكود مستخدم بالفعل' : 'الكود متاح',
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ في التحقق من الكود',
                'status_code' => 500
            ];
        }
    }

    /**
     * توليد كود مشروع تلقائي
     */
    public function generateCode(string $companyType): array
    {
        try {
            if (!in_array($companyType, ['A', 'K'])) {
                return [
                    'success' => false,
                    'message' => 'نوع الشركة غير صحيح',
                    'status_code' => 400
                ];
            }

            // الحصول على السنة والشهر الحالي
            $currentYear = date('Y');
            $currentMonth = date('m');

            // البحث عن آخر مشروع في نفس الشهر لنفس نوع الشركة (غير محذوف)
            $lastProject = Project::where('code', 'LIKE', $companyType . $currentYear . $currentMonth . '%')
                ->whereNull('deleted_at')
                ->orderBy('code', 'desc')
                ->first();

            // تحديد رقم المشروع التالي
            $projectNumber = 1;
            if ($lastProject) {
                // استخراج رقم المشروع من الكود
                $lastCode = $lastProject->code;
                $lastProjectNumber = (int) substr($lastCode, -2); // آخر رقمين
                $projectNumber = $lastProjectNumber + 1;
            }

            // تنسيق رقم المشروع ليكون رقمين
            $formattedProjectNumber = str_pad($projectNumber, 2, '0', STR_PAD_LEFT);

            // إنشاء الكود النهائي
            $generatedCode = $companyType . $currentYear . $currentMonth . $formattedProjectNumber;

            // التحقق من أن الكود غير مستخدم في مشاريع غير محذوفة
            $existingProject = Project::where('code', $generatedCode)
                ->whereNull('deleted_at')
                ->first();

            if ($existingProject) {
                // إذا كان الكود مستخدم، نزيد الرقم
                $projectNumber++;
                $formattedProjectNumber = str_pad($projectNumber, 2, '0', STR_PAD_LEFT);
                $generatedCode = $companyType . $currentYear . $currentMonth . $formattedProjectNumber;
            }

            return [
                'success' => true,
                'code' => $generatedCode,
                'company_type' => $companyType,
                'year' => $currentYear,
                'month' => $currentMonth,
                'project_number' => $projectNumber,
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Error generating project code: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ في توليد كود المشروع',
                'status_code' => 500
            ];
        }
    }
}

