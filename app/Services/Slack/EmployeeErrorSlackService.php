<?php

namespace App\Services\Slack;

use App\Models\EmployeeError;
use App\Models\User;

class EmployeeErrorSlackService extends BaseSlackService
{
    /**
     * إرسال إشعار عند تسجيل خطأ على موظف
     */
    public function sendErrorNotification(EmployeeError $error): bool
    {
        $employee = $error->user;

        if (!$employee) {
            return false;
        }

        $message = $this->buildErrorNotificationMessage($error);
        $context = 'إشعار تسجيل خطأ';
        $this->setNotificationContext($context);

        // استخدام Queue لتقليل الضغط على النظام
        return $this->sendSlackNotification($employee, $message, $context, true);
    }

    /**
     * إرسال إشعار للمديرين عن خطأ جوهري
     */
    public function sendCriticalErrorNotification(EmployeeError $error, User $manager): bool
    {
        $message = $this->buildCriticalErrorMessage($error);
        $context = 'إشعار خطأ جوهري';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($manager, $message, $context, true);
    }

    /**
     * إرسال إشعار عند تحديث خطأ
     */
    public function sendErrorUpdateNotification(EmployeeError $error): bool
    {
        $employee = $error->user;

        if (!$employee) {
            return false;
        }

        $message = $this->buildErrorUpdateMessage($error);
        $context = 'إشعار تحديث خطأ';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($employee, $message, $context, true);
    }

    /**
     * إرسال إشعار عند حذف خطأ
     */
    public function sendErrorDeletedNotification(EmployeeError $error): bool
    {
        $employee = $error->user;

        if (!$employee) {
            return false;
        }

        $message = $this->buildErrorDeletedMessage($error);
        $context = 'إشعار حذف خطأ';
        $this->setNotificationContext($context);

        return $this->sendSlackNotification($employee, $message, $context, true);
    }

    /**
     * بناء رسالة تسجيل خطأ
     */
    private function buildErrorNotificationMessage(EmployeeError $error): array
    {
        $error->load(['reportedBy', 'errorable']);

        // تحديد نوع الخطأ والأيقونة
        $errorTypeIcon = $error->error_type === 'critical' ? '🔴' : '⚠️';
        $errorTypeText = $error->error_type === 'critical' ? 'خطأ جوهري' : 'خطأ عادي';

        // تحديد فئة الخطأ
        $categoryMap = [
            'quality' => '🎯 جودة',
            'deadline' => '⏰ موعد',
            'communication' => '💬 تواصل',
            'technical' => '🔧 تقني',
            'procedural' => '📋 إجرائي',
            'other' => '📌 أخرى'
        ];
        $categoryText = $categoryMap[$error->error_category] ?? '📌 أخرى';

        // بناء معلومات المصدر (مهمة، مشروع، الخ)
        $sourceInfo = $this->getErrorSourceInfo($error);

        $blocks = [
            $this->buildHeader($errorTypeIcon . ' تم تسجيل خطأ'),
            $this->buildInfoSection([
                "*العنوان:*\n{$error->title}",
                "*النوع:*\n{$errorTypeText}"
            ]),
            $this->buildTextSection("*التفاصيل:*\n{$error->description}"),
            $this->buildInfoSection([
                "*الفئة:*\n{$categoryText}",
                "*سجله:*\n{$error->reportedBy->name}"
            ])
        ];

        // إضافة معلومات المصدر إن وجدت
        if ($sourceInfo) {
            $blocks[] = $this->buildTextSection("*المصدر:*\n{$sourceInfo}");
        }

        // زر عرض الأخطاء
        $errorsUrl = url('/employee-errors');
        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('📊 عرض أخطائي', $errorsUrl, 'primary')
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تم تسجيل {$errorTypeText} عليك",
            'blocks' => $blocks
        ];
    }

    /**
     * بناء رسالة خطأ جوهري للمديرين
     */
    private function buildCriticalErrorMessage(EmployeeError $error): array
    {
        $error->load(['user', 'reportedBy']);

        $categoryMap = [
            'quality' => '🎯 جودة',
            'deadline' => '⏰ موعد',
            'communication' => '💬 تواصل',
            'technical' => '🔧 تقني',
            'procedural' => '📋 إجرائي',
            'other' => '📌 أخرى'
        ];
        $categoryText = $categoryMap[$error->error_category] ?? '📌 أخرى';

        $sourceInfo = $this->getErrorSourceInfo($error);

        $blocks = [
            $this->buildHeader('🔴 تنبيه: خطأ جوهري'),
            $this->buildInfoSection([
                "*الموظف:*\n{$error->user->name}",
                "*العنوان:*\n{$error->title}"
            ]),
            $this->buildTextSection("*التفاصيل:*\n{$error->description}"),
            $this->buildInfoSection([
                "*الفئة:*\n{$categoryText}",
                "*سجله:*\n{$error->reportedBy->name}"
            ])
        ];

        if ($sourceInfo) {
            $blocks[] = $this->buildTextSection("*المصدر:*\n{$sourceInfo}");
        }

        $errorsUrl = url('/employee-errors');
        $blocks[] = $this->buildActionsSection([
            $this->buildActionButton('🔍 عرض التفاصيل', $errorsUrl, 'danger')
        ]);

        $blocks[] = $this->buildContextSection();

        return [
            'text' => "تم تسجيل خطأ جوهري على {$error->user->name}",
            'blocks' => $blocks
        ];
    }

    /**
     * بناء رسالة تحديث خطأ
     */
    private function buildErrorUpdateMessage(EmployeeError $error): array
    {
        $error->load(['reportedBy']);

        $errorTypeIcon = $error->error_type === 'critical' ? '🔴' : '⚠️';
        $errorTypeText = $error->error_type === 'critical' ? 'خطأ جوهري' : 'خطأ عادي';

        $errorsUrl = url('/employee-errors');

        return [
            'text' => "تم تحديث خطأ مسجل عليك",
            'blocks' => [
                $this->buildHeader('🔄 تحديث خطأ'),
                $this->buildInfoSection([
                    "*العنوان:*\n{$error->title}",
                    "*النوع:*\n{$errorTypeText}"
                ]),
                $this->buildTextSection("*التفاصيل المحدثة:*\n{$error->description}"),
                $this->buildActionsSection([
                    $this->buildActionButton('📊 عرض التفاصيل', $errorsUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * بناء رسالة حذف خطأ
     */
    private function buildErrorDeletedMessage(EmployeeError $error): array
    {
        $errorsUrl = url('/employee-errors');

        return [
            'text' => "تم حذف خطأ كان مسجلاً عليك",
            'blocks' => [
                $this->buildHeader('✅ حذف خطأ'),
                $this->buildTextSection("*تم حذف الخطأ:*\n{$error->title}"),
                $this->buildTextSection("تمت إزالة هذا الخطأ من سجلك. 🎉"),
                $this->buildActionsSection([
                    $this->buildActionButton('📊 عرض أخطائي', $errorsUrl)
                ]),
                $this->buildContextSection()
            ]
        ];
    }

    /**
     * الحصول على معلومات مصدر الخطأ
     */
    private function getErrorSourceInfo(EmployeeError $error): ?string
    {
        if (!$error->errorable) {
            return null;
        }

        $errorableType = get_class($error->errorable);

        switch ($errorableType) {
            case 'App\Models\TaskUser':
                $task = $error->errorable->task;
                $project = $task ? $task->project : null;
                $projectCode = $project ? $project->code : '';
                $taskName = $task ? $task->name : 'مهمة';
                return $projectCode ? "مهمة: [{$projectCode}] {$taskName}" : "مهمة: {$taskName}";

            case 'App\Models\TemplateTaskUser':
                $task = $error->errorable->templateTask;
                $project = $error->errorable->project;
                $projectCode = $project ? $project->code : '';
                $taskName = $task ? $task->name : 'مهمة قالب';
                return $projectCode ? "مهمة قالب: [{$projectCode}] {$taskName}" : "مهمة قالب: {$taskName}";

            case 'App\Models\ProjectServiceUser':
                $project = $error->errorable->project;
                $service = $error->errorable->service;
                $projectCode = $project ? $project->code : '';
                $projectName = $project ? $project->name : 'مشروع';
                $serviceName = $service ? $service->name : '';
                return $projectCode ? "مشروع: [{$projectCode}] {$projectName}" . ($serviceName ? " - {$serviceName}" : '') : $projectName;

            default:
                return null;
        }
    }
}

