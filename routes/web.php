<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\AbsenceRequestController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\OverTimeRequestsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SalarySheetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\EmployeeStatisticsController;
use App\Http\Controllers\SpecialCaseController;
use App\Http\Controllers\WorkShiftController;
use App\Http\Controllers\EmployeeCompetitionController;
use App\Http\Controllers\EmployeeBirthdayController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\FoodAllowanceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\EmployeeReportController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\UserBadgeController;
use App\Http\Controllers\BadgeDemotionRuleController;
use App\Http\Controllers\GraphicTaskTypeController;
use App\Http\Controllers\DepartmentRoleController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\Kpi\EvaluationCriteriaController;
use App\Http\Controllers\Kpi\RoleEvaluationMappingController;
use App\Http\Controllers\Kpi\KpiEvaluationController;
use App\Http\Controllers\EmployeeEvaluationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SeasonIntroController;
use App\Http\Controllers\RevisionPageController;
use App\Http\Controllers\EmployeeDeliveryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
})->name('/');

Route::get('/shared-attachments/{token}', [App\Http\Controllers\ProjectController::class, 'viewSharedAttachments'])->name('shared-attachments.view');
Route::get('/shared-attachments/{token}/download/{attachmentId}', [App\Http\Controllers\ProjectController::class, 'downloadSharedAttachment'])->name('shared-attachments.download');


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('social')->name('social.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/search', [PostController::class, 'search'])->name('search');

        // Posts Routes
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/like', [PostController::class, 'toggleLike'])->name('posts.like');

        // Comments Routes
        Route::post('/posts/{post}/comments', [PostController::class, 'addComment'])->name('posts.comments.store');
        Route::get('/posts/{post}/comments', [PostController::class, 'getComments'])->name('posts.comments.index');
        Route::delete('/comments/{comment}', [PostController::class, 'deleteComment'])->name('comments.destroy');
        Route::post('/comments/{comment}/like', [PostController::class, 'toggleCommentLike'])->name('comments.like');
    });

    Route::prefix('social/profile')->name('social.profile.')->group(function () {
        Route::get('/search', [UserProfileController::class, 'search'])->name('search');
        Route::get('/suggestions', [UserProfileController::class, 'suggestions'])->name('suggestions');
        Route::get('/{user}', [UserProfileController::class, 'show'])->name('show');
        Route::post('/{user}/follow', [UserProfileController::class, 'toggleFollow'])->name('follow');
        Route::get('/{user}/followers', [UserProfileController::class, 'followers'])->name('followers');
        Route::get('/{user}/following', [UserProfileController::class, 'following'])->name('following');
    });

        Route::get('/users/assign-work-shifts', [UserController::class, 'assignWorkShifts'])->name('users.assign-work-shifts')->middleware('role:manager');
        Route::post('/users/save-single-work-shift', [UserController::class, 'saveSingleWorkShift'])->name('users.save-single-work-shift')->middleware('role:manager');

        // Get all users for revision assignment (no HR middleware)
        Route::get('/users/all', [UserController::class, 'getAllUsersForRevision'])->name('users.all-for-revision');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/absence-requests/{absenceRequest}/status', [AbsenceRequestController::class, 'updateStatus'])
        ->name('absence-requests.updateStatus');
});

Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::post('/permission-requests/{permissionRequest}/update-status', [PermissionRequestController::class, 'updateStatus'])
        ->name('permission-requests.update-status');
    Route::patch('/permission-requests/{permissionRequest}/reset-status', [PermissionRequestController::class, 'resetStatus'])
        ->name('permission-requests.reset-status');
    Route::patch('/permission-requests/{permissionRequest}/modify', [PermissionRequestController::class, 'modifyResponse'])
        ->name('permission-requests.modify');
    Route::patch('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
        ->name('permission-requests.update-return-status');

    Route::patch('/overtime-requests/{overTimeRequest}/respond', [OverTimeRequestsController::class, 'updateStatus'])
        ->name('overtime-requests.respond');

    Route::resource('overtime-requests', OverTimeRequestsController::class)->except(['show']);

    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.records.index');
    Route::post('/attendance/import', [AttendanceRecordController::class, 'import'])->name('attendance.import');

    Route::resource('admin/notifications', AdminNotificationController::class, [
        'as' => 'admin'
    ]);

Route::resource('users', UserController::class)->except(['destroy']);
Route::post('user/import', [UserController::class, 'import'])->name('user.import');;


    Route::resource('work-shifts', WorkShiftController::class);
    Route::patch('/work-shifts/{workShift}/toggle-status', [WorkShiftController::class, 'toggleStatus'])->name('work-shifts.toggle-status');

    Route::resource('graphic-task-types', GraphicTaskTypeController::class)->except(['create', 'edit']);
    Route::post('/graphic-task-types/{graphicTaskType}/toggle-status', [GraphicTaskTypeController::class, 'toggleStatus'])->name('graphic-task-types.toggle-status');

    Route::resource('department-roles', DepartmentRoleController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('department-roles-hierarchy/manage', [DepartmentRoleController::class, 'manageHierarchy'])->name('department-roles.manage-hierarchy');
    Route::post('department-roles-hierarchy/save', [DepartmentRoleController::class, 'saveHierarchy'])->name('department-roles.save-hierarchy');

    // Role Approval Management Routes
    Route::prefix('admin/role-approvals')->name('admin.role-approvals.')->group(function () {
        Route::get('/', [App\Http\Controllers\RoleApprovalController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\RoleApprovalController::class, 'store'])->name('store');
        Route::put('/{roleApproval}', [App\Http\Controllers\RoleApprovalController::class, 'update'])->name('update');
        Route::delete('/{roleApproval}', [App\Http\Controllers\RoleApprovalController::class, 'destroy'])->name('destroy');
        Route::post('/{roleApproval}/toggle-status', [App\Http\Controllers\RoleApprovalController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/approvers-for-role', [App\Http\Controllers\RoleApprovalController::class, 'getApproversForRole'])->name('approvers-for-role');
        Route::get('/roles-can-approve', [App\Http\Controllers\RoleApprovalController::class, 'getRolesCanApprove'])->name('roles-can-approve');
    });


    Route::get('/salary-sheets', [SalarySheetController::class, 'index'])->name('salary-sheets.index');
    Route::post('/salary-sheets/upload', [SalarySheetController::class, 'upload'])->name('salary-sheets.upload');
    Route::get('/users/{user}/forbidden-permissions', [UserController::class, 'getForbiddenPermissions'])
    ->name('users.forbidden-permissions');

Route::get('/users/{user}/additional-permissions', [UserController::class, 'getAdditionalPermissions']);

Route::get('/roles/{role}/permissions', [UserController::class, 'getRolePermissions'])
    ->name('roles.permissions');

    Route::get('/audit-log', [App\Http\Controllers\AuditLogController::class, 'index'])
    ->name('audit-log.index')
    ->middleware(['auth', 'verified']);

    Route::resource('special-cases', SpecialCaseController::class);
Route::post('special-cases/import', [SpecialCaseController::class, 'import'])->name('special-cases.import');



Route::post('/users/{user}/roles-permissions', [UserController::class, 'updateRolesAndPermissions'])
    ->name('users.roles-permissions');
Route::get('/roles/{role}/permissions', [UserController::class, 'getRolePermissions'])
    ->name('roles.permissions')
    ->middleware('auth');
Route::post('/users/{user}/remove-roles', [UserController::class, 'removeRolesAndPermissions'])
    ->name('users.remove-roles');
Route::post('/users/{user}/reset-to-employee', [UserController::class, 'resetToEmployee'])
    ->name('users.reset-to-employee');
Route::get('/users-without-role', [UserController::class, 'getEmployeesWithoutRole'])
    ->name('users.without-role');
Route::post('/assign-employee-role', [UserController::class, 'assignEmployeeRole'])
    ->name('users.assign-employee-role');
});

Route::middleware(['auth', 'role:manager,employee'])->group(function () {
    Route::resource('overtime-requests', OverTimeRequestsController::class);
    Route::resource('overtime-requests', OverTimeRequestsController::class);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');
    Route::get('/user/{employee_id}/attendance-preview', [DashboardController::class, 'previewAttendance'])
        ->name('user.previewAttendance')
        ->where('employee_id', '[0-9]+');
    Route::get('/user/{employee_id}/attendance-report', [DashboardController::class, 'generateAttendancePDF'])
        ->name('user.downloadAttendanceReport');

    Route::get('/salary-sheet/{userId}/{month}/{filename}', function ($employee_id, $month, $filename) {
        $user = Auth::user();
        if ($user->employee_id != $employee_id && $user->role != 'manager') {
            abort(403, 'Unauthorized access');
        }
        $filePath = storage_path("app/private/salary_sheets/{$employee_id}/{$month}/{$filename}");
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }
        return response()->file($filePath);
    })->middleware('auth');

    Route::resource('/permission-requests', PermissionRequestController::class);
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages/{receiver}', [ChatController::class, 'getMessages']);
    Route::get('/chat/new-messages/{receiver}', [ChatController::class, 'getNewMessages']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/mark-seen', [ChatController::class, 'markAsSeen']);
    Route::get('/status/user/{user}', [ChatController::class, 'getUserStatus']);


});


Route::get('/attendance/{employee_id}/pdf', [DashboardController::class, 'generateAttendancePDF'])
    ->name('attendance.pdf')
    ->middleware(['auth', 'role:manager']);

Route::middleware(['auth'])->group(function () {
    Route::post('/overtime-requests/{overTimeRequest}/manager-status', [OverTimeRequestsController::class, 'updateManagerStatus'])
        ->name('overtime-requests.manager-status');
    Route::post('/overtime-requests/{overTimeRequest}/modify-manager-status', [OverTimeRequestsController::class, 'modifyManagerStatus'])
        ->name('overtime-requests.modify-manager-status');
    Route::post('/overtime-requests/{overTimeRequest}/reset-manager-status', [OverTimeRequestsController::class, 'resetManagerStatus'])
        ->name('overtime-requests.reset-manager-status');

    Route::post('/overtime-requests/{overTimeRequest}/hr-status', [OverTimeRequestsController::class, 'updateHrStatus'])
        ->name('overtime-requests.hr-status');
    Route::post('/overtime-requests/{overTimeRequest}/modify-hr-status', [OverTimeRequestsController::class, 'modifyHrStatus'])
        ->name('overtime-requests.modify-hr-status');
    Route::post('/overtime-requests/{overTimeRequest}/reset-hr-status', [OverTimeRequestsController::class, 'resetHrStatus'])
        ->name('overtime-requests.reset-hr-status');

    Route::get('/employee-statistics', [EmployeeStatisticsController::class, 'index'])
        ->name('employee-statistics.index');
    Route::get('/employee-statistics/{id}', [EmployeeStatisticsController::class, 'getEmployeeDetails'])
        ->name('employee-statistics.details');

    // Employee Competition Routes
    Route::get('/employee-competition', [EmployeeCompetitionController::class, 'index'])
        ->name('employee-competition.index')
        ->middleware('coming-soon');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/permission-requests', [PermissionRequestController::class, 'index'])
        ->name('permission-requests.index');

    Route::post('/permission-requests', [PermissionRequestController::class, 'store'])
        ->name('permission-requests.store');

    Route::put('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'update'])
        ->name('permission-requests.update');

    Route::delete('/permission-requests/{permissionRequest}', [PermissionRequestController::class, 'destroy'])
        ->name('permission-requests.destroy');

    Route::post('/permission-requests/{permissionRequest}/manager-status', [PermissionRequestController::class, 'updateManagerStatus'])
        ->name('permission-requests.manager-status');

    Route::post('/permission-requests/{permissionRequest}/modify-manager-status', [PermissionRequestController::class, 'modifyManagerStatus'])
        ->name('permission-requests.modify-manager-status');

    Route::post('/permission-requests/{permissionRequest}/reset-manager-status', [PermissionRequestController::class, 'resetManagerStatus'])
        ->name('permission-requests.reset-manager-status');

    Route::post('/permission-requests/{permissionRequest}/hr-status', [PermissionRequestController::class, 'updateHrStatus'])
        ->name('permission-requests.hr-status');

    Route::post('/permission-requests/{permissionRequest}/modify-hr-status', [PermissionRequestController::class, 'modifyHrStatus'])
        ->name('permission-requests.modify-hr-status');

    Route::post('/permission-requests/{permissionRequest}/reset-hr-status', [PermissionRequestController::class, 'resetHrStatus'])
        ->name('permission-requests.reset-hr-status');

    Route::patch('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
        ->name('permission-requests.return-status');

    Route::resource('/absence-requests', AbsenceRequestController::class)
        ->middleware(['permission:view_absence|create_absence|update_absence|delete_absence']);

    Route::patch('/absence-requests/{absenceRequest}/reset-status', [AbsenceRequestController::class, 'resetStatus'])
        ->name('absence-requests.reset-status')
        ->middleware(['permission:hr_respond_absence_request|manager_respond_absence_request']);

    Route::patch('/absence-requests/{id}/modify', [AbsenceRequestController::class, 'modifyResponse'])
        ->name('absence-requests.modify')
        ->middleware(['permission:hr_respond_absence_request|manager_respond_absence_request']);

    Route::post('/absence-requests/{absenceRequest}/status', [AbsenceRequestController::class, 'updateStatus'])
        ->name('absence-requests.updateStatus')
        ->middleware(['permission:hr_respond_absence_request|manager_respond_absence_request']);
});


Route::post('/permission-requests/{permissionRequest}/return-status', [PermissionRequestController::class, 'updateReturnStatus'])
    ->name('permission-requests.update-return-status');

Route::get('/employee-statistics/absences/{employee_id}', [EmployeeStatisticsController::class, 'getAbsences']);
Route::get('/employee-statistics/permissions/{employee_id}', [EmployeeStatisticsController::class, 'getPermissions']);
Route::get('/employee-statistics/overtimes/{employee_id}', [EmployeeStatisticsController::class, 'getOvertimes']);
Route::get('/employee-statistics/leaves/{employee_id}', [EmployeeStatisticsController::class, 'getLeaves']);
Route::get('/employee-statistics/current-month-leaves/{employee_id}', [EmployeeStatisticsController::class, 'getCurrentMonthLeaves'])
    ->name('employee-statistics.current-month-leaves');

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])
        ->name('notifications.unread');
    Route::post('/notifications/{decision}/acknowledge', [NotificationController::class, 'acknowledge'])
        ->name('notifications.acknowledge');
});


Route::post('/fcm-token', [App\Http\Controllers\FcmTokenController::class, 'update'])
    ->middleware('auth')
    ->name('fcm.token.update');

Route::post('/fcm-token/delete', [App\Http\Controllers\FcmTokenController::class, 'delete'])
    ->middleware('auth')
    ->name('fcm.token.delete');

// Employee Birthdays Route
Route::get('/employee-birthdays', [EmployeeBirthdayController::class, 'index'])
    ->name('employee-birthdays.index')
    ->middleware(['auth']);



// Override Jetstream's current-team.update route to use our custom controller
Route::put('/current-team', [\App\Http\Controllers\CustomCurrentTeamController::class, 'update'])
    ->name('current-team.update')->middleware('auth');


Route::get('absence-requests/{id}/audits', [App\Http\Controllers\AbsenceRequestController::class, 'showAudits'])
    ->name('absence-requests.audits')
    ->middleware(['auth', 'verified']);

Route::get('permission-requests/{id}/audits', [App\Http\Controllers\PermissionRequestController::class, 'showAudits'])
    ->name('permission-requests.audits')
    ->middleware(['auth', 'verified']);

Route::get('overtime-requests/{id}/audits', [App\Http\Controllers\OverTimeRequestsController::class, 'showAudits'])
    ->name('overtime-requests.audits')
    ->middleware(['auth', 'verified']);
// GeoIP Routes
Route::get('/geo', [App\Http\Controllers\GeoController::class, 'index'])->name('geo.index');
Route::post('/geo/check-ip', [App\Http\Controllers\GeoController::class, 'checkIp'])->name('geo.checkip');
Route::get('/geo/api', [App\Http\Controllers\GeoController::class, 'getLocationData'])->name('geo.api');
Route::get('/geo/ip/{ip}', [App\Http\Controllers\GeoController::class, 'getLocationData'])->name('geo.ip');

Route::get('/firebase-config', [App\Http\Controllers\FcmConfigController::class, 'getConfig'])
    ->middleware('auth')
    ->name('firebase.config');

// Attendance Routes
Route::middleware(['auth'])->prefix('attendance-system')->name('attendance.')->group(function () {
    // Dashboard for admins/managers
    Route::get('/dashboard', [AttendanceController::class, 'dashboard'])
        ->name('dashboard')
        ->middleware('role:manager');

    // Employee's own attendance
    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])
        ->name('my')
        ->middleware('company.ip');

    // Check-in and check-out
    Route::post('/check-in', [AttendanceController::class, 'checkIn'])
        ->name('check-in');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])
        ->name('check-out');

    // Cancel check-in and check-out
    Route::post('/cancel-check-in', [AttendanceController::class, 'cancelCheckIn'])
        ->name('cancel-check-in');
    Route::post('/cancel-check-out', [AttendanceController::class, 'cancelCheckOut'])
        ->name('cancel-check-out');

    // Admin/manager routes for viewing all attendance
    Route::get('/', [AttendanceController::class, 'index'])
        ->name('index')
        ->middleware('role:manager');

});

Route::middleware(['auth'])->group(function () {
    Route::get('food-allowances', [FoodAllowanceController::class, 'index'])->name('food-allowances.index');
    Route::post('food-allowances', [FoodAllowanceController::class, 'store'])->name('food-allowances.store');
    Route::put('food-allowances/{id}', [FoodAllowanceController::class, 'update'])->name('food-allowances.update');
    Route::delete('food-allowances/{id}', [FoodAllowanceController::class, 'destroy'])->name('food-allowances.destroy');

    // Employee food allowance requests
    Route::get('my-food-allowances', [FoodAllowanceController::class, 'myRequests'])->name('food-allowances.my-requests');
});

// Badge Management Routes
Route::middleware(['auth'])->group(function () {
    // Badges
    Route::resource('badges', BadgeController::class);

    // User Badges (Read-only - Automatic System)
    Route::get('/user-badges', [UserBadgeController::class, 'index'])->name('user-badges.index');
    Route::get('/user-badges/{userBadge}', [UserBadgeController::class, 'show'])->name('user-badges.show');
    Route::get('/users/{user}/badges', [UserBadgeController::class, 'userBadges'])->name('users.badges');
    Route::get('/badges/{badge}/users', [UserBadgeController::class, 'badgeUsers'])->name('badges.users');

        // Badge Demotion Rules
    Route::resource('badge-demotion-rules', BadgeDemotionRuleController::class);
    Route::patch('/badge-demotion-rules/{badgeDemotionRule}/toggle-active', [BadgeDemotionRuleController::class, 'toggleActive'])->name('badge-demotion-rules.toggle-active');

    // Additional Tasks (المهام الإضافية)
    // Applications Management - يجب أن يأتي قبل resource routes
    Route::get('/additional-tasks/applications', [App\Http\Controllers\AdditionalTaskController::class, 'applications'])->name('additional-tasks.applications');

    Route::get('/my-additional-tasks', [App\Http\Controllers\AdditionalTaskController::class, 'userTasks'])->name('additional-tasks.user-tasks');

    // Additional Tasks Resource Routes
    Route::resource('additional-tasks', App\Http\Controllers\AdditionalTaskController::class);
    Route::post('/additional-tasks/{additionalTask}/extend-time', [App\Http\Controllers\AdditionalTaskController::class, 'extendTime'])->name('additional-tasks.extend-time');
    Route::patch('/additional-tasks/{additionalTask}/toggle-status', [App\Http\Controllers\AdditionalTaskController::class, 'toggleStatus'])->name('additional-tasks.toggle-status');
    Route::post('/additional-tasks/{additionalTask}/accept', [App\Http\Controllers\AdditionalTaskController::class, 'acceptTask'])->name('additional-tasks.accept');
    Route::post('/additional-tasks/{additionalTask}/apply', [App\Http\Controllers\AdditionalTaskController::class, 'applyForTask'])->name('additional-tasks.apply');

    // Task User Management
    Route::post('/additional-task-users/{additionalTaskUser}/approve', [App\Http\Controllers\AdditionalTaskController::class, 'approveApplication'])->name('additional-task-users.approve');
    Route::post('/additional-task-users/{additionalTaskUser}/reject', [App\Http\Controllers\AdditionalTaskController::class, 'rejectApplication'])->name('additional-task-users.reject');


});


Route::middleware(['auth'])->group(function () {
    Route::resource('company-services', App\Http\Controllers\CompanyServiceController::class)->except(['destroy']);
    Route::patch('company-services/{companyService}/toggle-status', [App\Http\Controllers\CompanyServiceController::class, 'toggleStatus'])
        ->name('company-services.toggle-status');
    Route::post('company-services/{companyService}/roles', [App\Http\Controllers\CompanyServiceController::class, 'attachRole'])
        ->name('company-services.attach-role');
    Route::delete('company-services/{companyService}/roles/{roleId}', [App\Http\Controllers\CompanyServiceController::class, 'detachRole'])
        ->name('company-services.detach-role');

    // Service Dependencies Routes
    Route::get('company-services-dependencies/all', [App\Http\Controllers\CompanyServiceController::class, 'getAllDependencies'])
        ->name('company-services.dependencies.all');
    Route::post('company-services/dependencies', [App\Http\Controllers\CompanyServiceController::class, 'addDependency'])
        ->name('company-services.dependencies.add');
    Route::delete('company-services/{serviceId}/dependencies/{dependsOnServiceId}', [App\Http\Controllers\CompanyServiceController::class, 'removeDependency'])
        ->name('company-services.dependencies.remove');
    Route::patch('company-services/{companyService}/execution-order', [App\Http\Controllers\CompanyServiceController::class, 'updateExecutionOrder'])
        ->name('company-services.execution-order.update');

    // CRM Routes
    Route::resource('clients', App\Http\Controllers\ClientController::class);
    Route::get('/clients-crm/dashboard', [App\Http\Controllers\ClientController::class, 'crmDashboard'])->name('clients.crm-dashboard');
    Route::post('/clients/{client}/add-interest', [App\Http\Controllers\ClientController::class, 'addInterest'])->name('clients.add-interest');
    Route::delete('/clients/{client}/remove-interest', [App\Http\Controllers\ClientController::class, 'removeInterest'])->name('clients.remove-interest');
    Route::get('/clients/export', [App\Http\Controllers\ClientController::class, 'export'])->name('clients.export');

    // Call Logs Routes
    Route::resource('call-logs', App\Http\Controllers\CallLogController::class);



        // Client Tickets Routes - Simple System
    Route::resource('client-tickets', App\Http\Controllers\ClientTicketController::class);
    Route::get('/client-tickets-dashboard', [App\Http\Controllers\ClientTicketController::class, 'dashboard'])->name('client-tickets.dashboard');

    // Ticket Actions
    Route::post('/client-tickets/{clientTicket}/assign', [App\Http\Controllers\ClientTicketController::class, 'assign'])->name('client-tickets.assign');
    Route::post('/client-tickets/{clientTicket}/resolve', [App\Http\Controllers\ClientTicketController::class, 'resolve'])->name('client-tickets.resolve');
    Route::post('/client-tickets/{clientTicket}/reopen', [App\Http\Controllers\ClientTicketController::class, 'reopen'])->name('client-tickets.reopen');

    // Comments
    Route::post('/client-tickets/{clientTicket}/add-comment', [App\Http\Controllers\ClientTicketController::class, 'addComment'])->name('client-tickets.add-comment');

    // Multi-User Assignment
    Route::post('/client-tickets/{clientTicket}/add-user', [App\Http\Controllers\ClientTicketController::class, 'addUser'])->name('client-tickets.add-user');
    Route::post('/client-tickets/{clientTicket}/remove-user', [App\Http\Controllers\ClientTicketController::class, 'removeUser'])->name('client-tickets.remove-user');

    // Team Members for Mentions
Route::get('/client-tickets/{clientTicket}/team-members', [App\Http\Controllers\ClientTicketController::class, 'getTeamMembers'])->name('client-tickets.team-members');
    // Custom Fields Routes (قبل resource للأولوية)
    Route::get('/projects/{project}/custom-fields/edit', [App\Http\Controllers\ProjectController::class, 'editCustomFields'])->name('projects.custom-fields.edit');
    Route::put('/projects/{project}/custom-fields', [App\Http\Controllers\ProjectController::class, 'updateCustomFields'])->name('projects.custom-fields.update');
    // حذف المشاريع محظور نهائياً لحماية البيانات التاريخية
    Route::resource('projects', ProjectController::class)->except(['destroy']);

    // Project Pause Management Routes
    Route::get('/projects-pause', [App\Http\Controllers\ProjectPauseController::class, 'index'])->name('projects.pause.index');
    Route::post('/projects/{project}/pause', [App\Http\Controllers\ProjectPauseController::class, 'pause'])->name('projects.pause');
    Route::post('/projects/{project}/resume', [App\Http\Controllers\ProjectPauseController::class, 'resume'])->name('projects.resume');
    Route::post('/projects/pause-multiple', [App\Http\Controllers\ProjectPauseController::class, 'pauseMultiple'])->name('projects.pause.multiple');
    Route::get('/projects-pause/stats', [App\Http\Controllers\ProjectPauseController::class, 'stats'])->name('projects.pause.stats');

    // Project Delivery Management Routes
    Route::get('/projects-deliveries', [App\Http\Controllers\ProjectDeliveryController::class, 'index'])->name('projects.deliveries.index');
    Route::get('/projects/{project}/deliveries-history', [App\Http\Controllers\ProjectDeliveryController::class, 'getDeliveriesHistory'])->name('projects.deliveries.history');
    Route::get('/projects/{project}/services-status', [App\Http\Controllers\ProjectDeliveryController::class, 'getProjectServices'])->name('projects.services.status');
    Route::post('/projects/{project}/deliver', [App\Http\Controllers\ProjectDeliveryController::class, 'deliver'])->name('projects.deliver');
    Route::delete('/deliveries/{delivery}', [App\Http\Controllers\ProjectDeliveryController::class, 'destroy'])->name('deliveries.destroy');
    Route::get('/projects/{project}/deliveries', [App\Http\Controllers\ProjectDeliveryController::class, 'projectDeliveries'])->name('projects.deliveries.show');

    Route::get('/projects-preparation-period', [App\Http\Controllers\ProjectController::class, 'preparationPeriod'])->name('projects.preparation-period');
    Route::get('/projects-list-for-preparation', [App\Http\Controllers\ProjectController::class, 'getProjectsListForPreparation'])->name('projects.list-for-preparation');
    Route::post('/projects/add-preparation-period', [App\Http\Controllers\ProjectController::class, 'addPreparationPeriod'])->name('projects.add-preparation-period');
    Route::get('/projects-client-deliveries', [App\Http\Controllers\ProjectController::class, 'clientDeliveries'])->name('projects.client-deliveries');
    Route::get('/projects/{project}/sidebar-details', [App\Http\Controllers\ProjectController::class, 'getSidebarDetails'])->name('projects.sidebar-details');
    Route::post('/projects/generate-code', [App\Http\Controllers\ProjectController::class, 'generateCode'])->name('projects.generate-code');
    Route::post('/projects/check-code', [App\Http\Controllers\ProjectController::class, 'checkCodeAvailability'])->name('projects.check-code');
    // Route::get('/projects/{id}/check-deletion', [App\Http\Controllers\ProjectController::class, 'checkDeletionPossibility'])->name('projects.check-deletion'); // معطل: حذف المشاريع محظور
    Route::post('/projects/{project}/acknowledge', [App\Http\Controllers\ProjectController::class, 'acknowledgeProject'])->name('projects.acknowledge');
    Route::get('/projects/{project}/dates/history/{dateType?}', [App\Http\Controllers\ProjectController::class, 'showDateHistory'])->name('projects.dates.history');
    Route::post('/projects/{project}/attachments', [App\Http\Controllers\ProjectController::class, 'storeAttachment'])->name('projects.attachments.store');

    Route::get('/projects/attachments/view/{id}', [App\Http\Controllers\ProjectController::class, 'viewAttachment'])->name('projects.attachments.view');
    Route::get('/projects/attachments/download/{id}', [App\Http\Controllers\ProjectController::class, 'downloadAttachment'])->name('projects.attachments.download');

    // Project Custom Fields Management
    Route::resource('project-fields', App\Http\Controllers\ProjectFieldController::class);
    Route::post('/project-fields/update-order', [App\Http\Controllers\ProjectFieldController::class, 'updateOrder'])->name('project-fields.update-order');
    Route::post('/project-fields/{projectField}/toggle-active', [App\Http\Controllers\ProjectFieldController::class, 'toggleActive'])->name('project-fields.toggle-active');

    // Project Limits Management (الحد الشهري للمشاريع)
    Route::resource('project-limits', App\Http\Controllers\ProjectLimitController::class);
    Route::get('/project-limits-report', [App\Http\Controllers\ProjectLimitController::class, 'report'])->name('project-limits.report');
    Route::get('/project-limits-quick-stats', [App\Http\Controllers\ProjectLimitController::class, 'quickStats'])->name('project-limits.quick-stats');




    Route::post('projects/{project}/assign-participants', [App\Http\Controllers\ProjectController::class, 'assignParticipants'])->name('projects.assignParticipants');
    Route::post('projects/{project}/update-user-deadline', [App\Http\Controllers\ProjectController::class, 'updateUserDeadline'])->name('projects.updateUserDeadline');
    Route::post('projects/{project}/update-participant-role', [App\Http\Controllers\ProjectController::class, 'updateParticipantRole'])->name('projects.updateParticipantRole');

    // ✅ مسارات إدارة مهام القوالب
    Route::get('projects/{project}/available-template-tasks', [App\Http\Controllers\ProjectController::class, 'getAvailableTemplateTasks'])->name('projects.available-template-tasks');
    Route::post('projects/{project}/assign-selected-template-tasks', [App\Http\Controllers\ProjectController::class, 'assignSelectedTemplateTasks'])->name('projects.assign-selected-template-tasks');

    // مسار النظام الذكي لاقتراح الفرق
    Route::get('/projects/{project}/smart-team-suggestion/{serviceId}', [App\Http\Controllers\ProjectController::class, 'getSmartTeamSuggestion'])->name('projects.smart-team-suggestion');

    // Wasabi Presigned URL routes
    Route::post('/projects/{project}/presigned-url', [App\Http\Controllers\ProjectController::class, 'getPresignedUrl'])->name('projects.presigned-url');
    Route::post('/projects/attachments/{attachment}/confirm-upload', [App\Http\Controllers\ProjectController::class, 'confirmUpload'])->name('projects.attachments.confirm-upload');
    Route::post('projects/{project}/multiple-presigned-url', [ProjectController::class, 'getMultiplePresignedUrls'])->name('projects.multiple-presigned-url');

    // مسارات مشاركة المرفقات
    Route::post('/projects/attachments/share', [App\Http\Controllers\ProjectController::class, 'shareAttachments'])->name('projects.attachments.share');
    Route::get('/projects/{project}/users-for-sharing', [App\Http\Controllers\ProjectController::class, 'getAvailableUsersForSharing'])->name('projects.users-for-sharing');
    Route::get('/projects/{project}/participants', [App\Http\Controllers\ProjectController::class, 'getProjectParticipants'])->name('projects.participants');
    Route::get('/projects/attachments/{attachmentId}/shares', [App\Http\Controllers\ProjectController::class, 'getAttachmentShares'])->name('projects.attachments.shares');
    Route::delete('/projects/shares/{shareId}', [App\Http\Controllers\ProjectController::class, 'cancelShare'])->name('projects.shares.cancel');
    Route::delete('/projects/shares/token/{token}', [App\Http\Controllers\ProjectController::class, 'cancelShareByToken'])->name('projects.shares.cancel-by-token');
    Route::get('/projects/my-shares', [App\Http\Controllers\ProjectController::class, 'getUserShares'])->name('projects.my-shares');
    Route::get('/projects/share-statistics', [App\Http\Controllers\ProjectController::class, 'getShareStatistics'])->name('projects.share-statistics');

    // صفحة عرض الملفات المشاركة
    Route::get('/attachment-shares', [App\Http\Controllers\ProjectController::class, 'attachmentSharesIndex'])->name('attachment-shares.index');

    // مسارات الردود على المرفقات
    Route::get('/projects/attachments/{attachmentId}/replies', [App\Http\Controllers\ProjectController::class, 'getAttachmentReplies'])->name('projects.attachments.replies');

    // مسارات تأكيد المرفقات
    Route::post('/attachments/{attachment}/request-confirmation', [App\Http\Controllers\AttachmentConfirmationController::class, 'requestConfirmation'])->name('attachments.request-confirmation');
    Route::get('/attachment-confirmations', [App\Http\Controllers\AttachmentConfirmationController::class, 'index'])->name('attachment-confirmations.index');
    Route::get('/attachment-confirmations/my-requests', [App\Http\Controllers\AttachmentConfirmationController::class, 'myRequests'])->name('attachment-confirmations.my-requests');
    Route::post('/attachment-confirmations/{confirmation}/confirm', [App\Http\Controllers\AttachmentConfirmationController::class, 'confirm'])->name('attachment-confirmations.confirm');
    Route::post('/attachment-confirmations/{confirmation}/reject', [App\Http\Controllers\AttachmentConfirmationController::class, 'reject'])->name('attachment-confirmations.reject');
    Route::post('/attachment-confirmations/{confirmation}/reset', [App\Http\Controllers\AttachmentConfirmationController::class, 'reset'])->name('attachment-confirmations.reset');
    Route::get('/attachments/{attachment}/confirmation-status', [App\Http\Controllers\AttachmentConfirmationController::class, 'checkStatus'])->name('attachments.confirmation-status');

    Route::get('/projects/{project}/notes', [App\Http\Controllers\ProjectController::class, 'getNotes'])->name('projects.notes.index');
    Route::post('/projects/{project}/notes', [App\Http\Controllers\ProjectController::class, 'storeNote'])->name('projects.notes.store');
    Route::put('/projects/{project}/notes/{note}', [App\Http\Controllers\ProjectController::class, 'updateNote'])->name('projects.notes.update');
    Route::delete('/projects/{project}/notes/{note}', [App\Http\Controllers\ProjectController::class, 'deleteNote'])->name('projects.notes.destroy');
    Route::post('/projects/{project}/notes/{note}/toggle-pin', [App\Http\Controllers\ProjectController::class, 'toggleNotePin'])->name('projects.notes.toggle-pin');
    Route::post('/projects/{project}/notes/{note}/toggle-important', [App\Http\Controllers\ProjectController::class, 'toggleNoteImportant'])->name('projects.notes.toggle-important');
    Route::get('/projects/{project}/notes/stats', [App\Http\Controllers\ProjectController::class, 'getNotesStats'])->name('projects.notes.stats');
    Route::get('/projects/{project}/users-for-mentions', [App\Http\Controllers\ProjectController::class, 'getProjectUsersForMentions'])->name('projects.users-for-mentions');

    // Project Service Data Routes
    Route::get('/projects/{project}/service-data', [App\Http\Controllers\ProjectController::class, 'showServiceData'])->name('projects.service-data');
    Route::post('/projects/{project}/services/{service}/data', [App\Http\Controllers\ProjectController::class, 'updateServiceData'])->name('projects.update-service-data');
    Route::get('/projects/{project}/services/{service}/data', [App\Http\Controllers\ProjectController::class, 'getServiceData'])->name('projects.get-service-data');

    // Simple Overview Routes
    Route::get('/projects-services-overview', [App\Http\Controllers\ProjectController::class, 'simpleOverview'])->name('projects.services-overview');
    Route::get('/projects/{projectId}/services-simple', [App\Http\Controllers\ProjectController::class, 'getProjectServicesSimple'])->name('projects.services-simple');
    Route::get('/projects/{projectId}/details-sidebar', [App\Http\Controllers\ProjectController::class, 'getProjectDetailsForSidebar'])->name('projects.details-sidebar');
    Route::get('/projects/{projectId}/participants/{userId}/tasks', [App\Http\Controllers\ProjectController::class, 'getParticipantTasks'])->name('projects.participant-tasks');
    Route::get('/tasks/{taskType}/{taskId}/errors', [App\Http\Controllers\ProjectController::class, 'getTaskErrors'])->name('tasks.errors');
    Route::get('/tasks/{taskType}/{taskId}/revisions', [App\Http\Controllers\ProjectController::class, 'getTaskRevisions'])->name('tasks.revisions');

// Service Data Management Routes
Route::prefix('service-data')->name('service-data.')->group(function () {
    Route::get('/', [App\Http\Controllers\ServiceDataManagementController::class, 'index'])->name('index');
    Route::get('/{service}/manage', [App\Http\Controllers\ServiceDataManagementController::class, 'manageService'])->name('manage');
    Route::get('/{service}/create-field', [App\Http\Controllers\ServiceDataManagementController::class, 'createField'])->name('create-field');
    Route::post('/{service}/store-field', [App\Http\Controllers\ServiceDataManagementController::class, 'storeField'])->name('store-field');
    Route::get('/fields/{field}/edit', [App\Http\Controllers\ServiceDataManagementController::class, 'editField'])->name('edit-field');
    Route::put('/fields/{field}', [App\Http\Controllers\ServiceDataManagementController::class, 'updateField'])->name('update-field');
    Route::delete('/fields/{field}', [App\Http\Controllers\ServiceDataManagementController::class, 'deleteField'])->name('delete-field');
    Route::post('/fields/{field}/toggle-status', [App\Http\Controllers\ServiceDataManagementController::class, 'toggleFieldStatus'])->name('toggle-field-status');
    Route::post('/{service}/reorder-fields', [App\Http\Controllers\ServiceDataManagementController::class, 'reorderFields'])->name('reorder-fields');
    Route::post('/{service}/copy-fields', [App\Http\Controllers\ServiceDataManagementController::class, 'copyFieldsFromService'])->name('copy-fields');
});

// Performance Analysis Routes
Route::prefix('performance-analysis')->name('performance-analysis.')->group(function () {
    Route::get('/', [App\Http\Controllers\PerformanceAnalysisController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\PerformanceAnalysisController::class, 'show'])->name('show');
    Route::post('/search-by-code', [App\Http\Controllers\PerformanceAnalysisController::class, 'searchByCode'])->name('search-by-code');
    Route::get('/export/excel', [App\Http\Controllers\PerformanceAnalysisController::class, 'export'])->name('export');
});

    Route::resource('packages', App\Http\Controllers\PackageController::class);



});

// Project Dashboard Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/company-projects/dashboard', [App\Http\Controllers\ProjectDashboardController::class, 'index'])->name('company-projects.dashboard');

    Route::get('/company-projects/employee/{userId}/project/{projectId}/tasks', [App\Http\Controllers\ProjectDashboardController::class, 'employeeProjectTasks'])->name('company-projects.employee-project-tasks');
    Route::get('/company-projects/department-team', [App\Http\Controllers\ProjectDashboardController::class, 'getDepartmentTeam'])->name('company-projects.department-team');

    // Department and Team Routes
    Route::get('/departments/{department}', [App\Http\Controllers\ProjectDashboardController::class, 'showDepartment'])->name('departments.show');
    Route::get('/departments/{department}/teams/{teamId}', [App\Http\Controllers\ProjectDashboardController::class, 'showTeam'])->name('departments.teams.show');
    Route::get('/employees/{userId}/performance', [App\Http\Controllers\ProjectDashboardController::class, 'showEmployeePerformance'])->name('employees.performance');
});

// Task Management Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Task Templates
    Route::resource('task-templates', App\Http\Controllers\TaskTemplateController::class);
    Route::get('/task-templates/{id}/toggle-status', [App\Http\Controllers\TaskTemplateController::class, 'toggleStatus'])->name('task-templates.toggle-status');
    Route::post('/task-templates/{template}/tasks', [App\Http\Controllers\TaskTemplateController::class, 'storeTask'])->name('task-templates.tasks.store');
    Route::put('/template-tasks/{templateTask}', [App\Http\Controllers\TaskTemplateController::class, 'updateTask'])->name('template-tasks.update');
    Route::delete('/template-tasks/{templateTask}', [App\Http\Controllers\TaskTemplateController::class, 'destroyTask'])->name('template-tasks.destroy');
    Route::get('/service/{serviceId}/task-templates', [App\Http\Controllers\TaskTemplateController::class, 'getTemplatesForService'])->name('service.task-templates');

    // New route for updating template task status via AJAX (for Kanban board)
    Route::post('/template-tasks/{templateTask}/update-status', [App\Http\Controllers\TaskTemplateController::class, 'updateTaskStatus'])->name('template-tasks.update-status');

    // New route for updating regular task status
    Route::post('/task-users/{taskUser}/update-status', [App\Http\Controllers\TaskController::class, 'updateTaskUserStatus'])->name('task-users.update-status');

    // Tasks - Custom routes must come before resource routes to avoid conflicts
    Route::get('/tasks/users', [App\Http\Controllers\TaskController::class, 'getAllUsers'])->name('tasks.users');
    Route::get('/tasks/get-task-user-role-users', [App\Http\Controllers\TaskController::class, 'getTaskUserRoleUsers'])->name('tasks.get-task-user-role-users');
    Route::post('/tasks/store-from-template', [App\Http\Controllers\TaskController::class, 'storeFromTemplate'])->name('tasks.store-from-template');
    Route::get('/project/{projectId}/tasks', [App\Http\Controllers\TaskController::class, 'getTasksForProject'])->name('project.tasks');
    Route::post('/project/{projectId}/generate-tasks', [App\Http\Controllers\TaskController::class, 'generateTasksFromTemplates'])->name('project.generate-tasks');
    Route::get('/my-tasks', [App\Http\Controllers\TaskController::class, 'myTasks'])->name('tasks.my-tasks');
    Route::get('/service/{serviceId}/users', [App\Http\Controllers\TaskController::class, 'getUsersByService'])->name('service.users');
    Route::get('/role/{roleName}/users', [App\Http\Controllers\TaskController::class, 'getUsersByRole'])->name('role.users');

    // Tasks resource routes - must come after custom routes
    Route::resource('tasks', App\Http\Controllers\TaskController::class);

    // ========================
    // Task Items Routes (بنود المهام)
    // ========================

    // بنود المهام الأساسية (Tasks)
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/{task}/items', [App\Http\Controllers\TaskItemController::class, 'getTaskItems'])->name('items.index');
        Route::post('/{task}/items', [App\Http\Controllers\TaskItemController::class, 'addToTask'])->name('items.store');
        Route::put('/{task}/items/{itemId}', [App\Http\Controllers\TaskItemController::class, 'updateTaskItem'])->name('items.update');
        Route::delete('/{task}/items/{itemId}', [App\Http\Controllers\TaskItemController::class, 'deleteTaskItem'])->name('items.destroy');
    });

    // بنود مهام القوالب (TemplateTask)
    Route::prefix('template-tasks')->name('template-tasks.')->group(function () {
        Route::get('/{templateTask}/items', [App\Http\Controllers\TaskItemController::class, 'getTemplateTaskItems'])->name('items.index');
        Route::post('/{templateTask}/items', [App\Http\Controllers\TaskItemController::class, 'addToTemplateTask'])->name('items.store');
    });

    // بنود المستخدمين للمهام العادية (TaskUser)
    Route::prefix('task-users')->name('task-users.')->group(function () {
        Route::get('/{taskUser}/items', [App\Http\Controllers\TaskItemController::class, 'getTaskUserItems'])->name('items.index');
        Route::put('/{taskUser}/items/{itemId}/status', [App\Http\Controllers\TaskItemController::class, 'updateTaskUserItemStatus'])->name('items.update-status');
        Route::get('/{taskUser}/items/progress', [App\Http\Controllers\TaskItemController::class, 'getTaskProgress'])->name('items.progress');
    });

    // بنود المستخدمين لمهام القوالب (TemplateTaskUser)
    Route::prefix('template-task-users')->name('template-task-users.')->group(function () {
        Route::get('/{templateTaskUser}/items', [App\Http\Controllers\TaskItemController::class, 'getTemplateTaskUserItems'])->name('items.index');
        Route::put('/{templateTaskUser}/items/{itemId}/status', [App\Http\Controllers\TaskItemController::class, 'updateTemplateTaskUserItemStatus'])->name('items.update-status');
        Route::get('/{templateTaskUser}/items/progress', [App\Http\Controllers\TaskItemController::class, 'getTemplateTaskProgress'])->name('items.progress');
    });

        // Task Deliveries System (تسليمات التاسكات)
    Route::prefix('task-deliveries')->name('task-deliveries.')->group(function () {
        Route::get('/', [App\Http\Controllers\TaskDeliveriesController::class, 'index'])->name('index');
        Route::post('/approve-regular/{taskUser}', [App\Http\Controllers\TaskDeliveriesController::class, 'approveRegularTask'])->name('approve-regular');
        Route::post('/approve-template/{templateTaskUser}', [App\Http\Controllers\TaskDeliveriesController::class, 'approveTemplateTask'])->name('approve-template');
        Route::post('/reject-task', [App\Http\Controllers\TaskDeliveriesController::class, 'rejectTask'])->name('reject-task');
        Route::post('/task-details', [App\Http\Controllers\TaskDeliveriesController::class, 'getTaskDetails'])->name('task-details');
        Route::get('/project/{projectId}/tasks', [App\Http\Controllers\TaskDeliveriesController::class, 'getProjectTasks'])->name('project-tasks');
        Route::get('/stats', [App\Http\Controllers\TaskDeliveriesController::class, 'getApprovalStats'])->name('stats');
        Route::post('/update-points', [App\Http\Controllers\TaskDeliveriesController::class, 'updateTaskPoints'])->name('update-points');
        Route::get('/filter-approved', [App\Http\Controllers\TaskDeliveriesController::class, 'filterApprovedTasks'])->name('filter-approved');

        // نظام الاعتماد الإداري والفني للتاسكات المرتبطة بمشاريع
        // Task Users Approvals
        Route::post('/tasks/{taskUser}/grant-administrative-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'grantAdministrativeApprovalTask'])->name('grant-administrative-approval');
        Route::post('/tasks/{taskUser}/grant-technical-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'grantTechnicalApprovalTask'])->name('grant-technical-approval');
        Route::post('/tasks/{taskUser}/revoke-administrative-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'revokeAdministrativeApprovalTask'])->name('revoke-administrative-approval');
        Route::post('/tasks/{taskUser}/revoke-technical-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'revokeTechnicalApprovalTask'])->name('revoke-technical-approval');

        // Template Task Users Approvals
        Route::post('/template-tasks/{templateTaskUser}/grant-administrative-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'grantAdministrativeApprovalTemplate'])->name('template.grant-administrative-approval');
        Route::post('/template-tasks/{templateTaskUser}/grant-technical-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'grantTechnicalApprovalTemplate'])->name('template.grant-technical-approval');
        Route::post('/template-tasks/{templateTaskUser}/revoke-administrative-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'revokeAdministrativeApprovalTemplate'])->name('template.revoke-administrative-approval');
        Route::post('/template-tasks/{templateTaskUser}/revoke-technical-approval', [App\Http\Controllers\TaskDeliveriesController::class, 'revokeTechnicalApprovalTemplate'])->name('template.revoke-technical-approval');
    });


});

Route::middleware(['auth'])->group(function () {
    Route::get('/employee-reports', [EmployeeReportController::class, 'index'])->name('employee-reports.index');
    Route::get('/employee-reports/{id}', [EmployeeReportController::class, 'show'])->name('employee-reports.show');
});

Route::post('/projects/{project}/remove-participant', [App\Http\Controllers\ProjectController::class, 'removeParticipant'])
    ->name('projects.removeParticipant');

Route::get('/users/{user}/info', function(\App\Models\User $user) {
    return response()->json([
        'id' => $user->id,
        'name' => $user->name
    ]);
});

Route::get('/projects/{project}/template-tasks', [App\Http\Controllers\ProjectController::class, 'getTemplateTasks'])
    ->name('projects.template-tasks');
Route::get('/projects/{project}/regular-tasks', [App\Http\Controllers\ProjectController::class, 'getRegularTasks'])
    ->name('projects.regular-tasks');

// Task details endpoint for sidebar
Route::get('/task-details/{type}/{taskUserId}', [App\Http\Controllers\ProjectController::class, 'getTaskDetails'])
    ->name('task-details');

// Task notes endpoints
Route::prefix('task-notes')->middleware('auth')->group(function () {
    Route::get('/{taskType}/{taskUserId}', [App\Http\Controllers\TaskNoteController::class, 'index'])->name('task-notes.index');
    Route::post('/', [App\Http\Controllers\TaskNoteController::class, 'store'])->name('task-notes.store');
    Route::put('/{taskNote}', [App\Http\Controllers\TaskNoteController::class, 'update'])->name('task-notes.update');
    Route::delete('/{taskNote}', [App\Http\Controllers\TaskNoteController::class, 'destroy'])->name('task-notes.destroy');
});

// Task revisions endpoints
Route::prefix('task-revisions')->middleware('auth')->group(function () {
    // ✅ Routes الثابتة أولاً قبل الديناميكية (لتجنب التعارض)
    Route::get('/{revision}/download', [App\Http\Controllers\TaskRevisionController::class, 'downloadAttachment'])->name('task-revisions.download');
    Route::get('/{revision}/view', [App\Http\Controllers\TaskRevisionController::class, 'viewAttachment'])->name('task-revisions.view');

    // Routes الديناميكية
    Route::get('/{taskType}/{taskId}', [App\Http\Controllers\TaskRevisionController::class, 'index'])->name('task-revisions.index');
    Route::post('/', [App\Http\Controllers\TaskRevisionController::class, 'store'])->name('task-revisions.store');
    Route::put('/{revision}/status', [App\Http\Controllers\TaskRevisionController::class, 'updateStatus'])->name('task-revisions.update-status');
    Route::delete('/{revision}', [App\Http\Controllers\TaskRevisionController::class, 'destroy'])->name('task-revisions.destroy');

    // إدارة حالة التعديلات والوقت
    Route::post('/{revision}/start', [App\Http\Controllers\TaskRevisionController::class, 'startRevision'])->name('task-revisions.start');
    Route::post('/{revision}/pause', [App\Http\Controllers\TaskRevisionController::class, 'pauseRevision'])->name('task-revisions.pause');
    Route::post('/{revision}/resume', [App\Http\Controllers\TaskRevisionController::class, 'resumeRevision'])->name('task-revisions.resume');
    Route::post('/{revision}/complete', [App\Http\Controllers\TaskRevisionController::class, 'completeRevision'])->name('task-revisions.complete');
    Route::get('/active', [App\Http\Controllers\TaskRevisionController::class, 'getActiveRevision'])->name('task-revisions.active');
    Route::get('/user-stats', [App\Http\Controllers\TaskRevisionController::class, 'getUserRevisionStats'])->name('task-revisions.user-stats');

    // 🎯 جلب المراجعين فقط (hierarchy_level = 2)
    Route::get('/reviewers-only', [App\Http\Controllers\TaskRevisionController::class, 'getReviewersOnly'])->name('task-revisions.reviewers-only');

    // إدارة حالة المراجعة ووقت المراجع
    Route::post('/{revision}/start-review', [App\Http\Controllers\TaskRevisionController::class, 'startReview'])->name('task-revisions.start-review');
    Route::post('/{revision}/pause-review', [App\Http\Controllers\TaskRevisionController::class, 'pauseReview'])->name('task-revisions.pause-review');
    Route::post('/{revision}/resume-review', [App\Http\Controllers\TaskRevisionController::class, 'resumeReview'])->name('task-revisions.resume-review');
    Route::post('/{revision}/complete-review', [App\Http\Controllers\TaskRevisionController::class, 'completeReview'])->name('task-revisions.complete-review');

    // إعادة فتح التنفيذ والمراجعة
    Route::post('/{revision}/reopen-work', [App\Http\Controllers\TaskRevisionController::class, 'reopenWork'])->name('task-revisions.reopen-work');
    Route::post('/{revision}/reopen-review', [App\Http\Controllers\TaskRevisionController::class, 'reopenReview'])->name('task-revisions.reopen-review');
});

// Project revisions endpoints
Route::prefix('project-revisions')->middleware('auth')->group(function () {
    Route::get('/{projectId}', [App\Http\Controllers\TaskRevisionController::class, 'getProjectRevisions'])->name('project-revisions.index');
    Route::get('/{projectId}/all', [App\Http\Controllers\TaskRevisionController::class, 'getAllProjectRelatedRevisions'])->name('project-revisions.all');
    Route::post('/', [App\Http\Controllers\TaskRevisionController::class, 'store'])->name('project-revisions.store');
});

// General revisions endpoints
Route::prefix('general-revisions')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\TaskRevisionController::class, 'getGeneralRevisions'])->name('general-revisions.index');
    Route::post('/', [App\Http\Controllers\TaskRevisionController::class, 'store'])->name('general-revisions.store');
    Route::put('/{revision}', [App\Http\Controllers\TaskRevisionController::class, 'update'])->name('general-revisions.update');
});

// Revision reassignment endpoints
Route::prefix('task-revisions')->middleware('auth')->group(function () {
    Route::post('/{revision}/reassign-executor', [App\Http\Controllers\TaskRevisionController::class, 'reassignExecutor'])->name('task-revisions.reassign-executor');
    Route::post('/{revision}/reassign-reviewer', [App\Http\Controllers\TaskRevisionController::class, 'reassignReviewer'])->name('task-revisions.reassign-reviewer');
    Route::get('/{revision}/transfer-history', [App\Http\Controllers\TaskRevisionController::class, 'getTransferHistory'])->name('task-revisions.transfer-history');
    Route::get('/user-transfer-stats', [App\Http\Controllers\TaskRevisionController::class, 'getUserTransferStats'])->name('task-revisions.user-transfer-stats');
});

// Revision source endpoints
Route::prefix('revisions-by-source')->middleware('auth')->group(function () {
    Route::get('/internal', [App\Http\Controllers\TaskRevisionController::class, 'getInternalRevisions'])->name('revisions.internal');
    Route::get('/external', [App\Http\Controllers\TaskRevisionController::class, 'getExternalRevisions'])->name('revisions.external');
    Route::get('/project/{projectId}/stats', [App\Http\Controllers\TaskRevisionController::class, 'getProjectRevisionStats'])->name('revisions.project.stats');
});

// Revision Page endpoints
Route::prefix('revision-page')->middleware('auth')->group(function () {
    Route::get('/', [RevisionPageController::class, 'index'])->name('revision.page');
    Route::get('/all-revisions', [RevisionPageController::class, 'getAllRevisions'])->name('revision.page.all');
    Route::get('/my-revisions', [RevisionPageController::class, 'getMyRevisions'])->name('revision.page.my');
    Route::get('/my-created-revisions', [RevisionPageController::class, 'getMyCreatedRevisions'])->name('revision.page.created');
    Route::get('/stats', [RevisionPageController::class, 'getStats'])->name('revision.page.stats');
    Route::get('/projects-list', [RevisionPageController::class, 'getProjectsList'])->name('revision.page.projects');
    Route::get('/revision/{revision}', [RevisionPageController::class, 'getRevisionDetails'])->name('revision.page.details');

    // Revision Transfer Statistics Page
    Route::get('/transfer-statistics', [RevisionPageController::class, 'transferStatistics'])->name('revision.transfer-statistics');
    Route::get('/transfer-records', [RevisionPageController::class, 'getTransferRecords'])->name('revision.transfer-records');
    Route::get('/transfer-stats', [RevisionPageController::class, 'getTransferStats'])->name('revision.transfer-stats');

    // Project Services & Participants
    Route::get('/project-services', [RevisionPageController::class, 'getProjectServices'])->name('revision.project-services');
    Route::get('/service-participants', [RevisionPageController::class, 'getServiceParticipants'])->name('revision.service-participants');
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('meetings', 'App\Http\Controllers\MeetingController');
    Route::post('meetings/{meeting}/add-note', 'App\Http\Controllers\MeetingController@addNote')->name('meetings.add-note');
    Route::post('meetings/{meeting}/mark-attendance', 'App\Http\Controllers\MeetingController@markAttendance')->name('meetings.mark-attendance');
    Route::post('meetings/{meeting}/mark-completed', 'App\Http\Controllers\MeetingController@markCompleted')->name('meetings.mark-completed');
    Route::put('meetings/{meeting}/cancel', 'App\Http\Controllers\MeetingController@cancel')->name('meetings.cancel');
    Route::put('meetings/{meeting}/reset-status', 'App\Http\Controllers\MeetingController@resetStatus')->name('meetings.reset-status');

    Route::post('meetings/{meeting}/approve', 'App\Http\Controllers\MeetingController@approve')->name('meetings.approve');
    Route::post('meetings/{meeting}/reject', 'App\Http\Controllers\MeetingController@reject')->name('meetings.reject');
    Route::post('meetings/{meeting}/update-time', 'App\Http\Controllers\MeetingController@updateTime')->name('meetings.update-time');
    Route::post('meetings/{meeting}/undo-approval', 'App\Http\Controllers\MeetingController@undoApproval')->name('meetings.undo-approval');
});

Route::group(['middleware' => ['auth']], function () {
    Route::resource('skill-categories', App\Http\Controllers\SkillCategoryController::class);
    Route::resource('skills', App\Http\Controllers\SkillController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/employee-evaluations/my-evaluations', [EmployeeEvaluationController::class, 'myEvaluations'])->name('employee-evaluations.my-evaluations');
    Route::resource('employee-evaluations', EmployeeEvaluationController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/seasons/current', [App\Http\Controllers\SeasonController::class, 'current'])->name('seasons.current');
    Route::resource('seasons', App\Http\Controllers\SeasonController::class);

    // Season Intro Routes - يجب أن تكون خارج الميدل وير لتجنب التكرار اللانهائي
    Route::get('/season-intro/{season}', [SeasonIntroController::class, 'show'])->name('season.intro');
    Route::post('/season-intro/{season}/mark-seen', [SeasonIntroController::class, 'markAsSeen'])->name('season.mark-seen');
});


Route::middleware(['auth'])->prefix('seasons')->name('seasons.')->group(function () {
    Route::get('/statistics/my', [App\Http\Controllers\SeasonStatisticsController::class, 'myStatistics'])->name('statistics.my');
    Route::get('/statistics/user/{userId}', [App\Http\Controllers\SeasonStatisticsController::class, 'userStatistics'])->name('statistics.user');
    Route::get('/statistics/company', [App\Http\Controllers\SeasonStatisticsController::class, 'companyStatistics'])->name('statistics.company');
    Route::get('/statistics/all-users', [App\Http\Controllers\SeasonStatisticsController::class, 'allUsersStatistics'])->name('statistics.all-users');


    Route::get('/api/statistics/user/{userId}', [App\Http\Controllers\SeasonStatisticsController::class, 'getUserStatisticsJson'])->name('api.statistics.user');
    Route::get('/api/statistics/company', [App\Http\Controllers\SeasonStatisticsController::class, 'getCompanyStatisticsJson'])->name('api.statistics.company');
});

Route::middleware(['auth'])->prefix('projects')->name('projects.')->group(function () {
    // صفحة إحصائيات المشروع
    Route::get('/{project}/analytics', [App\Http\Controllers\ProjectController::class, 'analytics'])->name('analytics');



    Route::get('/{project}/employees/{user}/analytics', [App\Http\Controllers\ProjectController::class, 'getEmployeeProjectPerformance'])->name('employee-analytics');
    Route::get('/{project}/services-analytics', [App\Http\Controllers\ProjectController::class, 'serviceAnalyticsPage'])->name('service-analytics');
    Route::get('/{project}/service-analytics-data', [App\Http\Controllers\ProjectController::class, 'getServiceAnalytics'])->name('service-analytics-data');
    Route::put('/{project}/services/{service}/progress', [App\Http\Controllers\ProjectController::class, 'updateServiceProgress'])->name('service-progress.update');
    Route::get('/{project}/services/{service}/history', [App\Http\Controllers\ProjectController::class, 'getServiceProgressHistory'])->name('service-progress.history');
    Route::get('/{project}/service-alerts', [App\Http\Controllers\ProjectController::class, 'getServiceAlerts'])->name('service-alerts');
});

Route::middleware(['auth'])->prefix('task-transfer')->name('task-transfer.')->group(function () {
    Route::get('/', [App\Http\Controllers\TaskTransferController::class, 'index'])->name('index');
    Route::post('/transfer-task', [App\Http\Controllers\TaskTransferController::class, 'transferTask'])->name('transfer-task');
    Route::post('/transfer-template-task', [App\Http\Controllers\TaskTransferController::class, 'transferTemplateTask'])->name('transfer-template-task');
    Route::post('/cancel-task-transfer', [App\Http\Controllers\TaskTransferController::class, 'cancelTaskTransfer'])->name('cancel-task-transfer');
    Route::post('/cancel-template-task-transfer', [App\Http\Controllers\TaskTransferController::class, 'cancelTemplateTaskTransfer'])->name('cancel-template-task-transfer');
    Route::get('/check-transferability', [App\Http\Controllers\TaskTransferController::class, 'checkTransferability'])->name('check-transferability');
    Route::get('/available-users', [App\Http\Controllers\TaskTransferController::class, 'getAvailableUsers'])->name('available-users');
    Route::get('/history', [App\Http\Controllers\TaskTransferController::class, 'getUserTransferHistory'])->name('history');
});


Route::middleware(['auth'])->prefix('task-transfers')->name('task-transfers.')->group(function () {
    Route::get('/history', [App\Http\Controllers\TaskTransferController::class, 'transferHistory'])->name('history');
});

// Employee Deliveries Routes
Route::middleware(['auth'])->prefix('deliveries')->name('deliveries.')->group(function () {
    Route::get('/', [EmployeeDeliveryController::class, 'index'])->name('index');
    Route::get('/data', [EmployeeDeliveryController::class, 'getData'])->name('data');

    // Acknowledge routes (الاستلام)
    Route::post('/{delivery}/acknowledge', [EmployeeDeliveryController::class, 'acknowledge'])->name('acknowledge');
    Route::post('/{delivery}/unacknowledge', [EmployeeDeliveryController::class, 'unacknowledge'])->name('unacknowledge');

    // Approval routes (الاعتمادات - التسليم)
    Route::post('/{delivery}/grant-administrative-approval', [EmployeeDeliveryController::class, 'grantAdministrativeApproval'])->name('grant-administrative-approval');
    Route::post('/{delivery}/grant-technical-approval', [EmployeeDeliveryController::class, 'grantTechnicalApproval'])->name('grant-technical-approval');
    Route::post('/{delivery}/revoke-administrative-approval', [EmployeeDeliveryController::class, 'revokeAdministrativeApproval'])->name('revoke-administrative-approval');
    Route::post('/{delivery}/revoke-technical-approval', [EmployeeDeliveryController::class, 'revokeTechnicalApproval'])->name('revoke-technical-approval');
    Route::get('/{delivery}/approval-details', [EmployeeDeliveryController::class, 'getApprovalDetails'])->name('approval-details');

    // Lists
    Route::get('/awaiting-approval', [EmployeeDeliveryController::class, 'getDeliveriesAwaitingApproval'])->name('awaiting-approval');

    Route::get('/export', [EmployeeDeliveryController::class, 'export'])->name('export');

    // Project-related routes for level 3+ users
    Route::get('/projects', [EmployeeDeliveryController::class, 'getProjects'])->name('projects');
});


Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/user/current-points', [App\Http\Controllers\TaskTransferController::class, 'getCurrentUserPoints']);
});


Route::middleware(['auth'])->prefix('employee/profile')->name('employee.profile.')->group(function () {
    Route::get('/', [EmployeeProfileController::class, 'show'])->name('show');
    Route::get('/{user}', [EmployeeProfileController::class, 'show'])->name('show.user');
    Route::get('/export-pdf/{user?}', [EmployeeProfileController::class, 'exportPDF'])->name('export.pdf');

    Route::get('/refresh/{user?}', [EmployeeProfileController::class, 'refreshData'])->name('refresh');
});

// Task Attachments Routes - مرفقات المهام العادية
Route::middleware(['auth'])->prefix('task-attachments')->name('task-attachments.')->group(function () {
    // API routes for file operations
    Route::post('/presigned-url', [TaskAttachmentController::class, 'getPresignedUrl'])->name('presigned-url');
    Route::post('/upload', [TaskAttachmentController::class, 'uploadFile'])->name('upload');
    Route::post('/{attachmentId}/confirm', [TaskAttachmentController::class, 'confirmUpload'])->name('confirm');

    // File access routes
    Route::get('/{attachmentId}/view', [TaskAttachmentController::class, 'viewAttachment'])->name('view');
    Route::get('/{attachmentId}/download', [TaskAttachmentController::class, 'downloadAttachment'])->name('download');

    // Management routes
    Route::get('/task/{taskUserId}', [TaskAttachmentController::class, 'getTaskAttachments'])->name('task.index');
    Route::get('/task/{taskUserId}/page', [TaskAttachmentController::class, 'showTaskAttachments'])->name('task.show');

    // Statistics and admin routes
    Route::get('/stats', [TaskAttachmentController::class, 'getUserStats'])->name('stats');
    Route::post('/cleanup', [TaskAttachmentController::class, 'cleanupIncompleteUploads'])->name('cleanup');
});

// Evaluation Criteria Management Routes
Route::prefix('evaluation-criteria')->name('evaluation-criteria.')->middleware(['auth'])->group(function () {
    Route::get('/', [EvaluationCriteriaController::class, 'index'])->name('index');
    Route::get('/select-role', [EvaluationCriteriaController::class, 'selectRole'])->name('select-role');
    Route::get('/create', [EvaluationCriteriaController::class, 'create'])->name('create');
    Route::post('/', [EvaluationCriteriaController::class, 'store'])->name('store');
    Route::get('/{evaluationCriteria}', [EvaluationCriteriaController::class, 'show'])->name('show');
    Route::get('/{evaluationCriteria}/edit', [EvaluationCriteriaController::class, 'edit'])->name('edit');
    Route::put('/{evaluationCriteria}', [EvaluationCriteriaController::class, 'update'])->name('update');
    Route::delete('/{evaluationCriteria}', [EvaluationCriteriaController::class, 'destroy'])->name('destroy');

    // AJAX Routes
    Route::get('/ajax/by-role', [EvaluationCriteriaController::class, 'getCriteriaByRole'])->name('ajax.by-role');
});

// Role Evaluation Mapping Routes
Route::prefix('role-evaluation-mapping')->name('role-evaluation-mapping.')->middleware(['auth'])->group(function () {
    Route::get('/', [RoleEvaluationMappingController::class, 'index'])->name('index');
    Route::get('/create', [RoleEvaluationMappingController::class, 'create'])->name('create');
    Route::post('/', [RoleEvaluationMappingController::class, 'store'])->name('store');
    Route::get('/{roleEvaluationMapping}', [RoleEvaluationMappingController::class, 'show'])->name('show');
    Route::get('/{roleEvaluationMapping}/edit', [RoleEvaluationMappingController::class, 'edit'])->name('edit');
    Route::put('/{roleEvaluationMapping}', [RoleEvaluationMappingController::class, 'update'])->name('update');
    Route::delete('/{roleEvaluationMapping}', [RoleEvaluationMappingController::class, 'destroy'])->name('destroy');

    // AJAX Routes
    Route::get('/ajax/roles-can-evaluate', [RoleEvaluationMappingController::class, 'getRolesCanEvaluate'])->name('ajax.roles-can-evaluate');
    Route::get('/ajax/criteria-by-role', [RoleEvaluationMappingController::class, 'getCriteriaByRole'])->name('ajax.criteria-by-role');
    Route::get('/ajax/roles-by-department', [RoleEvaluationMappingController::class, 'getRolesByDepartment'])->name('ajax.roles-by-department');
});

// KPI Evaluation Routes
Route::prefix('kpi-evaluation')->name('kpi-evaluation.')->middleware(['auth'])->group(function () {
    Route::get('/', [KpiEvaluationController::class, 'index'])->name('index');
    Route::get('/create', [KpiEvaluationController::class, 'create'])->name('create');
    Route::post('/store', [KpiEvaluationController::class, 'store'])->name('store');

    // Details Route - يجب أن يكون قبل show route
    Route::get('/details', [KpiEvaluationController::class, 'details'])->name('details');

    // AJAX Routes - يجب أن تكون قبل {kpiEvaluation} route
    Route::get('/ajax/criteria-by-role', [KpiEvaluationController::class, 'getCriteriaByRole'])->name('ajax.criteria-by-role');
    Route::get('/ajax/user-projects', [KpiEvaluationController::class, 'getUserProjects'])->name('ajax.user-projects');
    Route::get('/{kpiEvaluation}/sidebar-details', [KpiEvaluationController::class, 'getSidebarDetails'])->name('sidebar-details');
    Route::get('/user-details/{userId}/{month}', [KpiEvaluationController::class, 'getUserDetails'])->name('user-details');

    // Show Route - يجب أن يكون أخيراً
    Route::get('/{kpiEvaluation}', [KpiEvaluationController::class, 'show'])->name('show');
});

// Activity Log Routes
Route::prefix('activity-log')->name('activity-log.')->middleware(['auth'])->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    Route::get('/user-daily', [ActivityLogController::class, 'userDaily'])->name('user-daily');
    Route::get('/user/{user}', [ActivityLogController::class, 'userActivities'])->name('user');
    Route::get('/model', [ActivityLogController::class, 'modelActivities'])->name('model');
    Route::get('/{activity}', [ActivityLogController::class, 'show'])->name('show');
    Route::delete('/{activity}', [ActivityLogController::class, 'destroy'])->name('destroy');
    Route::post('/clean', [ActivityLogController::class, 'clean'])->name('clean');
    Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
});

// إدارة الأدوار - Role Management Routes
Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [App\Http\Controllers\RoleController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\RoleController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\RoleController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\RoleController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [App\Http\Controllers\RoleController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\RoleController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\RoleController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/duplicate', [App\Http\Controllers\RoleController::class, 'duplicate'])->name('duplicate');
});

// ✅ تاريخ نقل المهام - Task Transfer History Routes
Route::prefix('transfer')->name('task-transfers.')->group(function () {
    Route::get('/history', [App\Http\Controllers\TaskTransferHistoryController::class, 'index'])->name('history');
    Route::get('/export', [App\Http\Controllers\TaskTransferHistoryController::class, 'export'])->name('export');
});

// ✅ أخطاء الموظفين - Employee Errors Routes
Route::prefix('employee-errors')->name('employee-errors.')->middleware(['auth'])->group(function () {
    // عرض أخطاء الموظف الحالي
    Route::get('/', [App\Http\Controllers\EmployeeErrorController::class, 'index'])->name('index');

    // جلب المهام/المشاريع الخاصة بموظف
    Route::get('/get-errorables', [App\Http\Controllers\EmployeeErrorController::class, 'getErrorables'])->name('get-errorables');

    // جلب الموظفين المتاحين لإضافة أخطاء عليهم
    Route::get('/get-available-users', [App\Http\Controllers\EmployeeErrorController::class, 'getAvailableUsers'])->name('get-available-users');

    // جلب مشاريع موظف معين
    Route::get('/get-employee-projects', [App\Http\Controllers\EmployeeErrorController::class, 'getEmployeeProjects'])->name('get-employee-projects');

    // عرض أخطاء موظف معين (للمديرين)
    Route::get('/user/{userId}', [App\Http\Controllers\EmployeeErrorController::class, 'userErrors'])->name('user');

    // تسجيل أخطاء على المهام
    Route::post('/task/{taskUserId}', [App\Http\Controllers\EmployeeErrorController::class, 'storeTaskError'])->name('task.store');
    Route::post('/template-task/{templateTaskUserId}', [App\Http\Controllers\EmployeeErrorController::class, 'storeTemplateTaskError'])->name('template-task.store');
    Route::post('/project/{projectServiceUserId}', [App\Http\Controllers\EmployeeErrorController::class, 'storeProjectError'])->name('project.store');

    // تحديث وحذف
    Route::put('/{errorId}', [App\Http\Controllers\EmployeeErrorController::class, 'update'])->name('update');
    Route::delete('/{errorId}', [App\Http\Controllers\EmployeeErrorController::class, 'destroy'])->name('destroy');

    // عرض تفاصيل خطأ
    Route::get('/{errorId}', [App\Http\Controllers\EmployeeErrorController::class, 'show'])->name('show');

    // أخطاء مشروع معين
    Route::get('/project/{projectId}/errors', [App\Http\Controllers\EmployeeErrorController::class, 'projectErrors'])->name('project.errors');

    // إحصائيات الفريق
    Route::get('/team/stats', [App\Http\Controllers\EmployeeErrorController::class, 'teamStats'])->name('team.stats');
});

// ✅ tracking-employee group
Route::prefix('tracking-employee')->name('tracking-employee.')->middleware(['auth'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [EmployeeController::class, 'index'])->name('dashboard');

    // Rutas de empleado individual
    Route::get('/employee/{employeeId}', [EmployeeController::class, 'employeeDetails'])->name('employee.details');
    Route::get('/employee/{employeeId}/monthly', [EmployeeController::class, 'employeeMonthly'])->name('employee.monthly');

    // Rutas de exportación de datos
    Route::get('/employee/{employeeId}/export/csv', [EmployeeController::class, 'exportCsv'])->name('employee.export.csv');
    Route::get('/employee/{employeeId}/export/excel', [EmployeeController::class, 'exportExcel'])->name('employee.export.excel');

    // Ruta para limpiar caché
    Route::get('/employee/{employeeId}/clear-cache', [EmployeeController::class, 'clearCache'])->name('employee.clear-cache');

    // Ruta de exportación de informe
    Route::get('/export-report', [ExportController::class, 'exportActivityReport'])->name('export.activity.report');
});

// ✅ Team Leader Auto-Assignment System (نظام التوزيع التلقائي الدوري)
Route::prefix('admin/team-leader-assignments')->name('team-leader-assignments.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/statistics', [App\Http\Controllers\TeamLeaderAssignmentController::class, 'statistics'])->name('statistics');
    Route::post('/reset', [App\Http\Controllers\TeamLeaderAssignmentController::class, 'resetRoundRobin'])->name('reset');
   Route::post('/{project}/assign', [App\Http\Controllers\TeamLeaderAssignmentController::class, 'assignSpecific'])->name('assign-specific');
});

// ✅ Employee Projects Management (إدارة مشاريع الموظف)
Route::prefix('employee/projects')->name('employee.projects.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\EmployeeProjectController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\EmployeeProjectController::class, 'show'])->name('show');
    Route::post('/{id}/update-status', [App\Http\Controllers\EmployeeProjectController::class, 'updateStatus'])->name('update-status');
    Route::post('/{id}/deliver', [App\Http\Controllers\EmployeeProjectController::class, 'deliverProject'])->name('deliver');
    Route::post('/{id}/undeliver', [App\Http\Controllers\EmployeeProjectController::class, 'undeliverProject'])->name('undeliver');
    Route::get('/quick-stats', [App\Http\Controllers\EmployeeProjectController::class, 'quickStats'])->name('quick-stats');

    // Team Leader: تحديث حالة الخدمة
    Route::post('/service/{projectId}/{serviceId}/update-status', [App\Http\Controllers\EmployeeProjectController::class, 'updateServiceStatus'])->name('service.update-status');
});




