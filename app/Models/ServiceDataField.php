<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class ServiceDataField extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'service_id',
        'field_name',
        'field_label',
        'field_type',
        'field_options',
        'order',
        'is_required',
        'is_active',
        'description',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع الخدمة
     */
    public function service()
    {
        return $this->belongsTo(CompanyService::class, 'service_id');
    }

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['field_name', 'field_label', 'field_type', 'field_options', 'is_required', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء حقل بيانات جديد',
                'updated' => 'تم تحديث حقل البيانات',
                'deleted' => 'تم حذف حقل البيانات',
                default => $eventName
            });
    }

    /**
     * Scope للحقول النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للترتيب حسب order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * الحصول على القيمة الافتراضية للحقل
     */
    public function getDefaultValue()
    {
        return match($this->field_type) {
            'boolean' => false,
            'date' => null,
            'dropdown' => $this->field_options[0] ?? null,
            'text' => '',
            default => null
        };
    }

    /**
     * التحقق من صحة القيمة المدخلة
     */
    public function validateValue($value): bool
    {
        if ($this->is_required && empty($value)) {
            return false;
        }

        return match($this->field_type) {
            'boolean' => $this->validateBoolean($value),
            'date' => $this->validateDate($value),
            'dropdown' => in_array($value, $this->field_options ?? []),
            'text' => $this->validateText($value),
            default => false
        };
    }

    /**
     * التحقق من صحة القيمة المنطقية
     */
    private function validateBoolean($value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        // قبول القيم النصية الشائعة
        $validValues = ['true', 'false', '1', '0', 'on', 'off', 'yes', 'no'];
        return in_array(strtolower((string)$value), $validValues);
    }

    /**
     * التحقق من صحة التاريخ
     */
    private function validateDate($value): bool
    {
        if (empty($value)) {
            return !$this->is_required;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    /**
     * التحقق من صحة النص
     */
    private function validateText($value): bool
    {
        if (empty($value)) {
            return !$this->is_required;
        }

        // التحقق من أن القيمة نصية وليست فارغة
        return is_string($value) && trim($value) !== '';
    }

    /**
     * الحصول على HTML input للحقل
     */
    public function getInputHtml($name, $value = null, $attributes = ''): string
    {
        $value = $value ?? $this->getDefaultValue();
        $required = $this->is_required ? 'required' : '';

        return match($this->field_type) {
            'boolean' => $this->getBooleanInput($name, $value, $attributes),
            'date' => $this->getDateInput($name, $value, $attributes, $required),
            'dropdown' => $this->getDropdownInput($name, $value, $attributes, $required),
            'text' => $this->getTextInput($name, $value, $attributes, $required),
            default => ''
        };
    }

    /**
     * HTML للـ Boolean Input
     */
    private function getBooleanInput($name, $value, $attributes): string
    {
        $checked = $value ? 'checked' : '';
        return <<<HTML
            <div class="flex items-center">
                <input type="checkbox"
                       name="{$name}"
                       id="{$name}"
                       value="1"
                       {$checked}
                       {$attributes}
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <label for="{$name}" class="ml-2 text-sm text-gray-600">{$this->field_label}</label>
            </div>
        HTML;
    }

    /**
     * HTML للـ Date Input
     */
    private function getDateInput($name, $value, $attributes, $required): string
    {
        $value = $value ?? '';
        return <<<HTML
            <div>
                <label for="{$name}" class="block text-sm font-medium text-gray-700">{$this->field_label}</label>
                <input type="date"
                       name="{$name}"
                       id="{$name}"
                       value="{$value}"
                       {$required}
                       {$attributes}
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        HTML;
    }

    /**
     * HTML للـ Dropdown Input
     */
    private function getDropdownInput($name, $value, $attributes, $required): string
    {
        $options = '';
        foreach ($this->field_options ?? [] as $option) {
            $selected = $option === $value ? 'selected' : '';
            $options .= "<option value=\"{$option}\" {$selected}>{$option}</option>";
        }

        return <<<HTML
            <div>
                <label for="{$name}" class="block text-sm font-medium text-gray-700">{$this->field_label}</label>
                <select name="{$name}"
                        id="{$name}"
                        {$required}
                        {$attributes}
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">اختر...</option>
                    {$options}
                </select>
            </div>
        HTML;
    }

    /**
     * HTML للـ Text Input
     */
    private function getTextInput($name, $value, $attributes, $required): string
    {
        return <<<HTML
            <div>
                <label for="{$name}" class="block text-sm font-medium text-gray-700">{$this->field_label}</label>
                <input type="text"
                       name="{$name}"
                       id="{$name}"
                       value="{$value}"
                       {$required}
                       {$attributes}
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                       placeholder="أدخل {$this->field_label}">
            </div>
        HTML;
    }
}
