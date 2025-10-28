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

class CheckEncryptionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encrypt:check-status {--model=all : اختر نموذج معين أو all للكل}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'فحص حالة تشفير البيانات الحساسة في قاعدة البيانات';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 فحص حالة التشفير في قاعدة البيانات...');

        $model = $this->option('model');

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

        $totalRecords = 0;
        $totalEncrypted = 0;
        $totalUnencrypted = 0;

        foreach ($targetModels as $modelName => $config) {
            $this->line("\n📋 فحص {$modelName}...");
            $result = $this->checkModelEncryption($config['model'], $config['fields'], $modelName);

            $totalRecords += $result['total'];
            $totalEncrypted += $result['encrypted'];
            $totalUnencrypted += $result['unencrypted'];
        }

        $this->newLine();
        $this->info("📊 تقرير شامل عن حالة التشفير:");
        $this->table(['النتيجة', 'العدد', 'النسبة'], [
            ['إجمالي السجلات', $totalRecords, '100%'],
            ['سجلات مشفرة', $totalEncrypted, $totalRecords > 0 ? round(($totalEncrypted / $totalRecords) * 100, 2) . '%' : '0%'],
            ['سجلات غير مشفرة', $totalUnencrypted, $totalRecords > 0 ? round(($totalUnencrypted / $totalRecords) * 100, 2) . '%' : '0%'],
        ]);

        return 0;
    }

    private function checkModelEncryption($modelClass, $fields, $modelName)
    {
        try {
            $tableName = (new $modelClass)->getTable();
            $records = DB::table($tableName)->get();
            $total = $records->count();

            if ($total === 0) {
                $this->line("   ⚠️  لا توجد سجلات في {$modelName}");
                return ['total' => 0, 'encrypted' => 0, 'unencrypted' => 0];
            }

            $encrypted = 0;
            $unencrypted = 0;
            $examples = [];

            foreach ($records as $record) {
                $recordEncrypted = false;
                $recordData = [];

                foreach ($fields as $field) {
                    if (isset($record->$field) && $record->$field !== null) {
                        $isFieldEncrypted = $this->isEncrypted($record->$field);

                        if ($isFieldEncrypted) {
                            $recordEncrypted = true;
                        }

                        $recordData[$field] = [
                            'value' => substr($record->$field, 0, 50) . (strlen($record->$field) > 50 ? '...' : ''),
                            'encrypted' => $isFieldEncrypted
                        ];
                    }
                }

                if ($recordEncrypted) {
                    $encrypted++;
                } else {
                    $unencrypted++;
                }

                // أضافة مثال للسجلات القليلة الأولى
                if (count($examples) < 3) {
                    $examples[] = [
                        'id' => $record->id ?? 'N/A',
                        'status' => $recordEncrypted ? 'مشفر' : 'غير مشفر',
                        'fields' => $recordData
                    ];
                }
            }

            $this->line("   📈 إجمالي السجلات: {$total}");
            $this->line("   🔐 مشفر: {$encrypted}");
            $this->line("   📄 غير مشفر: {$unencrypted}");

            // عرض أمثلة
            if (!empty($examples)) {
                $this->line("\n   🔍 أمثلة من السجلات:");
                foreach ($examples as $example) {
                    $this->line("   • ID: {$example['id']} - الحالة: {$example['status']}");
                    foreach ($example['fields'] as $fieldName => $fieldData) {
                        $status = $fieldData['encrypted'] ? '🔐' : '📄';
                        $this->line("     {$status} {$fieldName}: {$fieldData['value']}");
                    }
                }
            }

            return ['total' => $total, 'encrypted' => $encrypted, 'unencrypted' => $unencrypted];

        } catch (\Exception $e) {
            $this->error("   ❌ خطأ في فحص {$modelName}: " . $e->getMessage());
            return ['total' => 0, 'encrypted' => 0, 'unencrypted' => 0];
        }
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
