<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class UsersImport implements ToModel, WithEvents
{
    protected $duplicates = [];
    protected $skippedRows = [];
    protected $importedUsers = [];
    protected $updatedUsers = [];

    public function model(array $row)
    {
        // Skip header row or empty rows
        if (empty($row) || (isset($row[0]) && $row[0] == 'م')) {
            return null;
        }

        // Log all incoming data for debugging
        Log::info('Processing row import:', ['row' => $row]);

        if (empty($row[1]) || empty($row[4]) || empty($row[9])) {
            $this->skippedRows[] = [
                'employee_name' => $row[1] ?? 'غير متوفر',
                'reason' => 'بيانات إلزامية مفقودة (الاسم أو رقم الهاتف أو الرقم الوطني)'
            ];
            Log::warning('Missing required data', ['row' => $row]);
            return null;
        }

        // Improve checks for existing user with more detailed conditions
        $existingUserQuery = User::query();

        if (!empty($row[20])) {
            $existingUserQuery->orWhere('employee_id', $row[20]);
        }

        if (!empty($row[9])) {
            $existingUserQuery->orWhere('national_id_number', $row[9]);
        }

        $existingUser = $existingUserQuery->first();

        if ($existingUser) {
            try {
                // تحديث بيانات الموظف الموجود بدلاً من تخطيه
                $existingUser->update([
                    'name' => $row[1] ?? $existingUser->name,
                    'gender' => $row[2] ?? $existingUser->gender,
                    'address' => $row[3] ?? $existingUser->address,
                    'phone_number' => $row[4] ?? $existingUser->phone_number,
                    'email' => $row[5] ?? $existingUser->email,
                    'date_of_birth' => $dateOfBirth ?? $existingUser->date_of_birth,
                    'education_level' => $row[10] ?? $existingUser->education_level,
                    'marital_status' => $row[11] ?? $existingUser->marital_status,
                    'number_of_children' => isset($row[12]) && is_numeric($row[12]) ? (int)$row[12] : $existingUser->number_of_children,
                    'department' => $row[13] ?? $existingUser->department,
                    'start_date_of_employment' => $startDateOfEmployment ?? $existingUser->start_date_of_employment,
                    'employee_status' => $row[18] ?? $existingUser->employee_status,
                ]);

                // تسجيل الموظف الذي تم تحديثه في مصفوفة المحدثين بدلاً من المكررين
                $this->updatedUsers[] = [
                    'employee_name' => $row[1],
                    'employee_id' => $row[20] ?? 'N/A',
                    'national_id' => $row[9] ?? 'N/A'
                ];

                Log::info('User data updated', [
                    'existing_user' => $existingUser->toArray(),
                    'row' => $row
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Failed to update user', [
                    'exception' => $e->getMessage(),
                    'user_id' => $existingUser->id,
                    'row' => $row
                ]);

                $this->skippedRows[] = [
                    'employee_name' => $row[1] ?? 'غير متوفر',
                    'reason' => 'خطأ في تحديث الحساب: ' . $e->getMessage()
                ];

                return null;
            }
        }

        $dateOfBirth = null;
        if (!empty($row[6]) && is_numeric($row[6])) {
            try {
                $dateOfBirth = Carbon::createFromFormat('Y-m-d', Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[6]))->format('Y-m-d'));
            } catch (\Exception $e) {
                Log::error('Error parsing date of birth', ['exception' => $e->getMessage(), 'row' => $row]);
                $dateOfBirth = null;
            }
        }

        $startDateOfEmployment = null;
        if (!empty($row[14]) && is_numeric($row[14])) {
            try {
                $startDateOfEmployment = Carbon::createFromFormat('Y-m-d', Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[14]))->format('Y-m-d'));
            } catch (\Exception $e) {
                Log::error('Error parsing employment start date', ['exception' => $e->getMessage(), 'row' => $row]);
                $startDateOfEmployment = null;
            }
        }

        $age = 0;
        if ($dateOfBirth) {
            $now = Carbon::now();
            $age = abs($now->diffInYears($dateOfBirth));
            if ($now->month < $dateOfBirth->month || ($now->month == $dateOfBirth->month && $now->day < $dateOfBirth->day)) {
                $age--;
            }
        }

        try {
            $user = new User([
                'employee_id' => isset($row[20]) && !empty($row[20]) ? (int)$row[20] : null,
                'name' => $row[1] ?? null,
                'gender' => $row[2] ?? null,
                'password' => bcrypt($row[9] ?? 'default_password'),
                'address' => $row[3] ?? null,
                'phone_number' => $row[4] ?? null,
                'email' => $row[5] ?? null,
                'date_of_birth' => $dateOfBirth ?? '1900-01-01',
                'national_id_number' => $row[9] ?? null,
                'education_level' => $row[10] ?? null,
                'marital_status' => $row[11] ?? null,
                'number_of_children' => isset($row[12]) && is_numeric($row[12]) ? (int)$row[12] : 0,
                'department' => $row[13] ?? null,
                'start_date_of_employment' => $startDateOfEmployment ?? '1900-01-01',
                'employee_status' => $row[18] ?? 'active',
                'last_contract_start_date' => null,
                'last_contract_end_date' => null,
                'job_progression' => null,
            ]);

            $user->save();

            // Track successfully imported users
            $this->importedUsers[] = [
                'employee_name' => $row[1] ?? null,
                'employee_id' => $row[20] ?? null,
                'national_id' => $row[9] ?? null
            ];

            Log::info('User imported successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'employee_id' => $user->employee_id
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'exception' => $e->getMessage(),
                'row' => $row
            ]);

            $this->skippedRows[] = [
                'employee_name' => $row[1] ?? 'غير متوفر',
                'reason' => 'خطأ في إنشاء الحساب: ' . $e->getMessage()
            ];

            return null;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $message = '';

                if (!empty($this->duplicates)) {
                    $message .= "تم تخطي الموظفين المكررين التالية:\n";
                    foreach ($this->duplicates as $duplicate) {
                        $message .= "- {$duplicate['employee_name']} (#{$duplicate['employee_id']}): {$duplicate['reason']}\n";
                    }
                }

                if (!empty($this->skippedRows)) {
                    $message .= "\nتم تخطي السجلات التالية:\n";
                    foreach ($this->skippedRows as $skipped) {
                        $message .= "- {$skipped['employee_name']}: {$skipped['reason']}\n";
                    }
                }

                if (!empty($this->importedUsers)) {
                    $message .= "\nتم استيراد الموظفين التالية بنجاح:\n";
                    foreach ($this->importedUsers as $imported) {
                        $message .= "- {$imported['employee_name']} (#{$imported['employee_id']})\n";
                    }

                    Log::info('Import completed', [
                        'imported_count' => count($this->importedUsers),
                        'imported_users' => $this->importedUsers
                    ]);
                }

                if (!empty($this->updatedUsers)) {
                    $message .= "\nتم تحديث الموظفين التالية:\n";
                    foreach ($this->updatedUsers as $updated) {
                        $message .= "- {$updated['employee_name']} (#{$updated['employee_id']})\n";
                    }

                    Log::info('Updated users', [
                        'updated_count' => count($this->updatedUsers),
                        'updated_users' => $this->updatedUsers
                    ]);
                }

                if (!empty($message)) {
                    Session::flash('import_summary', $message);
                    Session::flash('skipped_count', count($this->duplicates) + count($this->skippedRows));
                    Session::flash('imported_count', count($this->importedUsers));
                    Session::flash('updated_count', count($this->updatedUsers));
                }
            }
        ];
    }
}
