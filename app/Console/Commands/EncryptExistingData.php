<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;
use App\Models\SalarySheet;
use App\Models\Message;
use App\Models\PermissionRequest;
use App\Models\AbsenceRequest;
use App\Models\OverTimeRequests;
use App\Models\CallLog;
use App\Models\ClientTicket;
use App\Models\TicketComment;
use App\Models\EmployeeEvaluation;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Exception;

class EncryptExistingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encrypt:existing-data {--model=all : اختر نموذج معين أو all للكل} {--force : فرض التشفير حتى لو كانت البيانات مشفرة}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تشفير البيانات الحساسة الموجودة في قاعدة البيانات';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 بدء عملية تشفير البيانات الحساسة...');

        $model = $this->option('model');
        $force = $this->option('force');

        $models = [
            'users' => ['model' => User::class, 'fields' => ['national_id_number', 'email', 'phone_number', 'address', 'fcm_token']],
            'salary_sheets' => ['model' => SalarySheet::class, 'fields' => ['file_path', 'original_filename']],
            'clients' => ['model' => Client::class, 'fields' => ['name', 'emails', 'phones']],
            'messages' => ['model' => Message::class, 'fields' => ['content']],
            'permission_requests' => ['model' => PermissionRequest::class, 'fields' => ['reason', 'manager_rejection_reason', 'hr_rejection_reason']],
            'absence_requests' => ['model' => AbsenceRequest::class, 'fields' => ['reason', 'manager_rejection_reason', 'hr_rejection_reason']],
            'overtime_requests' => ['model' => OverTimeRequests::class, 'fields' => ['reason', 'manager_rejection_reason', 'hr_rejection_reason']],
            'call_logs' => ['model' => CallLog::class, 'fields' => ['call_summary', 'notes']],
            'client_tickets' => ['model' => ClientTicket::class, 'fields' => ['description', 'resolution_notes']],
            'ticket_comments' => ['model' => TicketComment::class, 'fields' => ['comment']],
            'employee_evaluations' => ['model' => EmployeeEvaluation::class, 'fields' => ['notes']],
            'attendances' => ['model' => Attendance::class, 'fields' => ['notes']],
        ];

        if ($model !== 'all' && !isset($models[$model])) {
            $this->error("النموذج '$model' غير موجود. النماذج المتاحة: " . implode(', ', array_keys($models)));
            return 1;
        }

        $targetModels = $model === 'all' ? $models : [$model => $models[$model]];

        $totalEncrypted = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($targetModels as $modelName => $config) {
            $this->line("\n📋 معالجة {$modelName}...");
            $result = $this->encryptModelData($config['model'], $config['fields'], $force);

            $totalEncrypted += $result['encrypted'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
        }

        $this->newLine();
        $this->info("✅ انتهت عملية التشفير!");
        $this->table(['النتيجة', 'العدد'], [
            ['تم التشفير', $totalEncrypted],
            ['تم التخطي', $totalSkipped],
            ['أخطاء', $totalErrors],
        ]);

        return 0;
    }

    private function encryptModelData($modelClass, $fields, $force = false)
    {
        $encrypted = 0;
        $skipped = 0;
        $errors = 0;

        try {
            $records = $modelClass::all();
            $total = $records->count();

            if ($total === 0) {
                $this->line("   ⚠️  لا توجد سجلات في {$modelClass}");
                return ['encrypted' => 0, 'skipped' => 0, 'errors' => 0];
            }

            $progressBar = $this->output->createProgressBar($total);
            $progressBar->start();

            foreach ($records as $record) {
                $needsUpdate = false;
                $updateData = [];

                foreach ($fields as $field) {
                    if ($record->$field === null) {
                        continue;
                    }

                    // تحقق من أن البيانات غير مشفرة أو force=true
                    if ($force || !$this->isEncrypted($record->$field)) {
                        $updateData[$field] = $record->$field;
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    try {
                        // استخدام updateQuietly لتجنب events وre-encryption
                        $record->updateQuietly($updateData);
                        $encrypted++;
                    } catch (Exception $e) {
                        $this->newLine();
                        $this->error("   ❌ خطأ في تشفير السجل {$record->id}: " . $e->getMessage());
                        $errors++;
                    }
                } else {
                    $skipped++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
            $this->line("   ✅ {$encrypted} سجل تم تشفيره، {$skipped} تم تخطيه");

        } catch (Exception $e) {
            $this->error("   ❌ خطأ في معالجة {$modelClass}: " . $e->getMessage());
            $errors++;
        }

        return ['encrypted' => $encrypted, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function isEncrypted($value)
    {
        if (is_null($value) || $value === '') {
            return false;
        }

        // للـ arrays (JSON)
        if (is_array($value)) {
            return false;
        }

        // تحقق من أن القيمة تحتوي على format التشفير في Laravel
        return str_starts_with($value, 'eyJpdiI6') || str_starts_with($value, 'ey');
    }
}
