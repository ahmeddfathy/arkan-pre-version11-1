<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'hr_respond_absence_request',
            'manager_respond_absence_request',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'hr_respond_permission_request',
            'manager_respond_permission_request',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'hr_respond_overtime_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_team_data',
            'view_department_data',
            'view_all_data',

            'manage_reviews',
            'schedule_client_meetings',
            'create_teams',
            'create_own_tasks',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $allAllowedPermissions = array_unique($permissions);

        $existingPermissions = Permission::all();
        foreach ($existingPermissions as $existingPermission) {
            if (!in_array($existingPermission->name, $allAllowedPermissions)) {
                $existingPermission->delete();
            }
        }

        $hr = Role::firstOrCreate(['name' => 'hr']);
        $companyManager = Role::firstOrCreate(['name' => 'company_manager']);
        $projectManager = Role::firstOrCreate(['name' => 'project_manager']);
        $operationsManager = Role::firstOrCreate(['name' => 'operations_manager']);
        $teamLeader = Role::firstOrCreate(['name' => 'team_leader']);
        $departmentManager = Role::firstOrCreate(['name' => 'department_manager']);

        $technicalTeamLeader = Role::firstOrCreate(['name' => 'technical_team_leader']);
        $marketingTeamLeader = Role::firstOrCreate(['name' => 'marketing_team_leader']);
        $customerServiceTeamLeader = Role::firstOrCreate(['name' => 'customer_service_team_leader']);
        $coordinationTeamLeader = Role::firstOrCreate(['name' => 'coordination_team_leader']);

        $technicalDepartmentManager = Role::firstOrCreate(['name' => 'technical_department_manager']);
        $marketingDepartmentManager = Role::firstOrCreate(['name' => 'marketing_department_manager']);
        $customerServiceDepartmentManager = Role::firstOrCreate(['name' => 'customer_service_department_manager']);
        $coordinationDepartmentManager = Role::firstOrCreate(['name' => 'coordination_department_manager']);

        $operationAssistant = Role::firstOrCreate(['name' => 'operation_assistant']);
        $generalReviewer = Role::firstOrCreate(['name' => 'general_reviewer']);
        $technicalSupport = Role::firstOrCreate(['name' => 'technical_support']);

        // New Employee Roles
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $salesEmployee = Role::firstOrCreate(['name' => 'sales_employee']);
        $marketingTeamEmployee = Role::firstOrCreate(['name' => 'marketing_team_employee']);
        $financialTeamEmployee = Role::firstOrCreate(['name' => 'financial_team_employee']);
        $technicalTeamEmployee = Role::firstOrCreate(['name' => 'technical_team_employee']);

        // New Reviewer Roles
        $technicalReviewer = Role::firstOrCreate(['name' => 'technical_reviewer']);
        $marketingReviewer = Role::firstOrCreate(['name' => 'marketing_reviewer']);
        $financialReviewer = Role::firstOrCreate(['name' => 'financial_reviewer']);

        $employee->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'view_own_data',
        ]);

        $managerBasePermissions = [
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'manager_respond_absence_request',
            'manager_respond_permission_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_team_data',
        ];

        $departmentManagerBasePermissions = array_merge($managerBasePermissions, [
            'view_department_data',
        ]);

        $teamLeader->givePermissionTo($managerBasePermissions);
        $departmentManager->givePermissionTo($departmentManagerBasePermissions);

        $technicalTeamLeader->givePermissionTo($managerBasePermissions);

        $marketingTeamLeader->givePermissionTo($managerBasePermissions);

        $customerServiceTeamLeader->givePermissionTo($managerBasePermissions);

        $coordinationTeamLeader->givePermissionTo($managerBasePermissions);

        $technicalDepartmentManager->givePermissionTo($departmentManagerBasePermissions);

        $marketingDepartmentManager->givePermissionTo($departmentManagerBasePermissions);

        $customerServiceDepartmentManager->givePermissionTo($departmentManagerBasePermissions);

        $coordinationDepartmentManager->givePermissionTo($departmentManagerBasePermissions);


        $projectManager->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'manager_respond_absence_request',
            'manager_respond_permission_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_department_data',
            'view_team_data',
        ]);

        // Operations Manager - نفس صلاحيات مدير المشاريع
        $operationsManager->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'manager_respond_absence_request',
            'manager_respond_permission_request',
            'manager_respond_overtime_request',
            'view_own_data',
            'view_department_data',
            'view_team_data',
        ]);

        $hr->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'hr_respond_absence_request',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'hr_respond_permission_request',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'hr_respond_overtime_request',
            'view_all_data',
            'manage_reviews',
        ]);

        $companyManager->givePermissionTo([
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'hr_respond_absence_request',
            'manager_respond_absence_request',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'hr_respond_permission_request',
            'manager_respond_permission_request',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'hr_respond_overtime_request',
            'manager_respond_overtime_request',
            'view_all_data',
        ]);

        $users = User::whereDoesntHave('roles')->get();
        foreach ($users as $user) {
            $user->assignRole('employee');
        }

        $employeePermissions = [
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'view_own_data',
        ];

        User::all()->each(function ($user) use ($employeePermissions) {
            $user->givePermissionTo($employeePermissions);
        });

        $mandatoryPermissions = [
            'employee' => ['view_own_data'],
            'manager' => ['view_own_data', 'view_team_data'],
            'hr' => ['view_own_data', 'view_all_data']
        ];

        // Add permissions for Technical Support
        $technicalSupport->givePermissionTo([
            'view_own_data',
            'schedule_client_meetings',
        ]);

        // Add permissions for New Employee Roles
        $employeeBasePermissions = [
            'view_absence',
            'create_absence',
            'update_absence',
            'delete_absence',
            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
            'view_overtime',
            'create_overtime',
            'update_overtime',
            'delete_overtime',
            'view_own_data',
        ];

        $salesEmployee->givePermissionTo($employeeBasePermissions);
        $marketingTeamEmployee->givePermissionTo($employeeBasePermissions);
        $financialTeamEmployee->givePermissionTo($employeeBasePermissions);
        $technicalTeamEmployee->givePermissionTo($employeeBasePermissions);

        // Add permissions for New Reviewer Roles
        $reviewerBasePermissions = array_merge($employeeBasePermissions, [
            'view_team_data',
        ]);

        $technicalReviewer->givePermissionTo($reviewerBasePermissions);

        $marketingReviewer->givePermissionTo($reviewerBasePermissions);

        $financialReviewer->givePermissionTo($reviewerBasePermissions);
    }
}
