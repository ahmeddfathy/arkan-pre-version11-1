<?php

namespace App\Services;

use App\Models\ServiceDataField;
use App\Models\CompanyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceDataManagementService
{
    /**
     * الحصول على جميع حقول البيانات لخدمة معينة
     */
    public function getServiceFields($serviceId)
    {
        return ServiceDataField::where('service_id', $serviceId)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * إنشاء حقل بيانات جديد
     */
    public function createField(array $data)
    {
        try {
            DB::beginTransaction();

            // إنشاء اسم الحقل تلقائياً من التسمية العربية
            if (isset($data['field_label']) && !isset($data['field_name'])) {
                $data['field_name'] = $data['field_label'];
            }

            // تحديد الترتيب التلقائي
            if (!isset($data['order'])) {
                $maxOrder = ServiceDataField::where('service_id', $data['service_id'])->max('order');
                $data['order'] = ($maxOrder ?? 0) + 1;
            }

            // التحقق من نوع الحقل
            if ($data['field_type'] === 'dropdown' && empty($data['field_options'])) {
                throw new \Exception('يجب تحديد خيارات للحقل من نوع Dropdown');
            }

            // تنظيف field_options للأنواع الأخرى
            if ($data['field_type'] !== 'dropdown') {
                $data['field_options'] = null;
            }

            $field = ServiceDataField::create($data);

            DB::commit();

            return [
                'success' => true,
                'field' => $field,
                'message' => 'تم إنشاء الحقل بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating service data field', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحقل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تحديث حقل بيانات
     */
    public function updateField($fieldId, array $data)
    {
        try {
            DB::beginTransaction();

            $field = ServiceDataField::findOrFail($fieldId);

            // إنشاء اسم الحقل تلقائياً من التسمية العربية
            if (isset($data['field_label']) && !isset($data['field_name'])) {
                $data['field_name'] = $data['field_label'];
            }

            // التحقق من نوع الحقل
            if (isset($data['field_type']) && $data['field_type'] === 'dropdown' && empty($data['field_options'])) {
                throw new \Exception('يجب تحديد خيارات للحقل من نوع Dropdown');
            }

            // تنظيف field_options للأنواع الأخرى
            if (isset($data['field_type']) && $data['field_type'] !== 'dropdown') {
                $data['field_options'] = null;
            }

            $field->update($data);

            DB::commit();

            return [
                'success' => true,
                'field' => $field,
                'message' => 'تم تحديث الحقل بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating service data field', [
                'error' => $e->getMessage(),
                'field_id' => $fieldId,
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحقل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * حذف حقل بيانات
     */
    public function deleteField($fieldId)
    {
        try {
            $field = ServiceDataField::findOrFail($fieldId);
            $field->delete();

            return [
                'success' => true,
                'message' => 'تم حذف الحقل بنجاح'
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting service data field', [
                'error' => $e->getMessage(),
                'field_id' => $fieldId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الحقل: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إعادة ترتيب الحقول
     */
    public function reorderFields($serviceId, array $fieldIds)
    {
        try {
            DB::beginTransaction();

            foreach ($fieldIds as $index => $fieldId) {
                ServiceDataField::where('id', $fieldId)
                    ->where('service_id', $serviceId)
                    ->update(['order' => $index + 1]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم إعادة الترتيب بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error reordering service data fields', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة الترتيب: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تبديل حالة الحقل (نشط/غير نشط)
     */
    public function toggleFieldStatus($fieldId)
    {
        try {
            $field = ServiceDataField::findOrFail($fieldId);
            $field->update(['is_active' => !$field->is_active]);

            return [
                'success' => true,
                'is_active' => $field->is_active,
                'message' => $field->is_active ? 'تم تفعيل الحقل' : 'تم تعطيل الحقل'
            ];

        } catch (\Exception $e) {
            Log::error('Error toggling service data field status', [
                'error' => $e->getMessage(),
                'field_id' => $fieldId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة الحقل'
            ];
        }
    }

    /**
     * الحصول على البيانات الافتراضية لخدمة في مشروع جديد
     */
    public function getDefaultServiceData($serviceId)
    {
        $fields = $this->getServiceFields($serviceId);
        $data = [];

        foreach ($fields as $field) {
            $data[$field->field_name] = $field->getDefaultValue();
        }

        return $data;
    }

    /**
     * التحقق من صحة بيانات الخدمة المدخلة
     */
    public function validateServiceData($serviceId, array $inputData)
    {
        $fields = $this->getServiceFields($serviceId);
        $errors = [];

        foreach ($fields as $field) {
            $value = $inputData[$field->field_name] ?? null;

            if ($field->is_required && empty($value)) {
                $errors[$field->field_name] = "الحقل {$field->field_label} مطلوب";
                continue;
            }

            if (!empty($value) && !$field->validateValue($value)) {
                $errors[$field->field_name] = "قيمة غير صحيحة للحقل {$field->field_label}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * نسخ حقول الخدمة من خدمة إلى أخرى
     */
    public function copyFieldsFromService($sourceServiceId, $targetServiceId)
    {
        try {
            DB::beginTransaction();

            $sourceFields = ServiceDataField::where('service_id', $sourceServiceId)->get();

            foreach ($sourceFields as $sourceField) {
                ServiceDataField::create([
                    'service_id' => $targetServiceId,
                    'field_name' => $sourceField->field_name,
                    'field_label' => $sourceField->field_label,
                    'field_type' => $sourceField->field_type,
                    'field_options' => $sourceField->field_options,
                    'order' => $sourceField->order,
                    'is_required' => $sourceField->is_required,
                    'is_active' => $sourceField->is_active,
                    'description' => $sourceField->description,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم نسخ الحقول بنجاح'
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error copying service data fields', [
                'error' => $e->getMessage(),
                'source_service_id' => $sourceServiceId,
                'target_service_id' => $targetServiceId
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء نسخ الحقول'
            ];
        }
    }

    /**
     * الحصول على إحصائيات الحقول
     */
    public function getFieldStatistics($serviceId)
    {
        $fields = ServiceDataField::where('service_id', $serviceId)->get();

        return [
            'total' => $fields->count(),
            'active' => $fields->where('is_active', true)->count(),
            'required' => $fields->where('is_required', true)->count(),
            'by_type' => [
                'boolean' => $fields->where('field_type', 'boolean')->count(),
                'date' => $fields->where('field_type', 'date')->count(),
                'dropdown' => $fields->where('field_type', 'dropdown')->count(),
            ]
        ];
    }
}

