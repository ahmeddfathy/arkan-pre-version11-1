<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DepartmentRolesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // الحصول على جميع الأقسام المتاحة من جدول المستخدمين
        $departments = User::whereNotNull('department')
                           ->distinct()
                           ->pluck('department')
                           ->filter()
                           ->toArray();

        // الحصول على جميع الأدوار
        $roles = Role::all()->keyBy('name');

        // تعريف روابط الأقسام مع الأدوار
        $departmentRoleMappings = [
            // القسم التقني
            'Technical' => [
                'technical_team_leader',
                'technical_department_manager',
                'technical_team_employee',
                'employee',
            ],

            // قسم التسويق
            'Marketing' => [
                'marketing_team_leader',
                'marketing_department_manager',
                'marketing_team_employee',
                'employee',
            ],

            // قسم خدمة العملاء
            'Customer Service' => [
                'customer_service_team_leader',
                'customer_service_department_manager',
                'employee',
                'technical_support',
            ],

            // قسم التنسيق
            'Coordination' => [
                'coordination_team_leader',
                'coordination_department_manager',
                'operation_assistant',
                'employee',
            ],

            // قسم المبيعات
            'Sales' => [
                'sales_employee',
                'employee',
                'team_leader',
            ],

            // القسم المالي
            'Financial' => [
                'financial_team_employee',
                'employee',
                'team_leader',
            ],

            // الموارد البشرية
            'HR' => [
                'hr',
                'employee',
            ],

            // الإدارة العامة
            'Management' => [
                'company_manager',
                'project_manager',
                'department_manager',
            ],
        ];

        // إنشاء الروابط
        foreach ($departments as $department) {
            if (isset($departmentRoleMappings[$department])) {
                $this->createDepartmentRoles($department, $departmentRoleMappings[$department], $roles);
            } else {
                // إنشاء روابط افتراضية للأقسام غير المعرفة
                $this->createDefaultDepartmentRoles($department, $roles);
            }
        }

        $this->command->info('تم إنشاء روابط أدوار الأقسام بنجاح');
    }

    /**
     * إنشاء روابط محددة لقسم معين
     */
    private function createDepartmentRoles($departmentName, $roleMapping, $roles)
    {
        foreach ($roleMapping as $roleName => $settings) {
            if (isset($roles[$roleName])) {
                $this->insertDepartmentRole(
                    $departmentName,
                    $roles[$roleName]->id,
                    $settings['is_primary'],
                    $settings['can_assign'],
                    "ربط تلقائي للقسم {$departmentName}"
                );
            }
        }
    }

    /**
     * إنشاء روابط افتراضية للأقسام غير المعرفة
     */
    private function createDefaultDepartmentRoles($departmentName, $roles)
    {
        $defaultRoles = [
            'employee' => ['is_primary' => true, 'can_assign' => true],
            'team_leader' => ['is_primary' => false, 'can_assign' => true],
            'department_manager' => ['is_primary' => false, 'can_assign' => true],
        ];

        foreach ($defaultRoles as $roleName => $settings) {
            if (isset($roles[$roleName])) {
                $this->insertDepartmentRole(
                    $departmentName,
                    $roles[$roleName]->id,
                    $settings['is_primary'],
                    $settings['can_assign'],
                    "ربط افتراضي للقسم {$departmentName}"
                );
            }
        }
    }

    /**
     * إدراج ربط دور بقسم
     */
    private function insertDepartmentRole($departmentName, $roleId, $isPrimary, $canAssign, $notes)
    {
        // التحقق من عدم وجود الربط مسبقاً
        $exists = DB::table('department_roles')
            ->where('department_name', $departmentName)
            ->where('role_id', $roleId)
            ->exists();

        if (!$exists) {
            DB::table('department_roles')->insert([
                'department_name' => $departmentName,
                'role_id' => $roleId,
                'is_primary' => $isPrimary,
                'can_assign' => $canAssign,
                'notes' => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("تم ربط الدور {$roleId} بالقسم {$departmentName}");
        }
    }
}
