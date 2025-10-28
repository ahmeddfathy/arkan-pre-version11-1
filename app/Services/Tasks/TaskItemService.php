<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TemplateTask;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Traits\HasNTPTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * خدمة إدارة بنود المهام
 *
 * تتعامل مع:
 * - إضافة/تعديل/حذف البنود من المهام الأساسية (tasks, template_tasks)
 * - نسخ البنود للمستخدمين عند التعيين
 * - تحديث حالة البنود للمستخدمين (تم/لم يتم/لا ينطبق)
 */
class TaskItemService
{
    use HasNTPTime;
    /**
     * إضافة بند جديد للمهمة
     */
    public function addItemToTask(Task $task, array $itemData): array
    {
        try {
            $items = $task->items ?? [];

            $newItem = [
                'id' => Str::uuid()->toString(),
                'title' => $itemData['title'],
                'description' => $itemData['description'] ?? null,
                'order' => count($items) + 1,
            ];

            $items[] = $newItem;
            $task->items = $items;
            $task->save();

            Log::info('Added item to task', [
                'task_id' => $task->id,
                'item_id' => $newItem['id'],
                'item_title' => $newItem['title']
            ]);

            return [
                'success' => true,
                'message' => 'تم إضافة البند بنجاح',
                'item' => $newItem,
                'items' => $items
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add item to task', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة البند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إضافة بند جديد لمهمة القالب
     */
    public function addItemToTemplateTask(TemplateTask $templateTask, array $itemData): array
    {
        try {
            $items = $templateTask->items ?? [];

            $newItem = [
                'id' => Str::uuid()->toString(),
                'title' => $itemData['title'],
                'description' => $itemData['description'] ?? null,
                'order' => count($items) + 1,
            ];

            $items[] = $newItem;
            $templateTask->items = $items;
            $templateTask->save();

            Log::info('Added item to template task', [
                'template_task_id' => $templateTask->id,
                'item_id' => $newItem['id'],
                'item_title' => $newItem['title']
            ]);

            return [
                'success' => true,
                'message' => 'تم إضافة البند بنجاح',
                'item' => $newItem,
                'items' => $items
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add item to template task', [
                'template_task_id' => $templateTask->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة البند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تحديث بند في المهمة
     */
    public function updateTaskItem(Task $task, string $itemId, array $itemData): array
    {
        try {
            $items = $task->items ?? [];
            $itemFound = false;

            foreach ($items as &$item) {
                if ($item['id'] === $itemId) {
                    $item['title'] = $itemData['title'] ?? $item['title'];
                    $item['description'] = $itemData['description'] ?? $item['description'];
                    $item['order'] = $itemData['order'] ?? $item['order'];
                    $itemFound = true;
                    break;
                }
            }

            if (!$itemFound) {
                return [
                    'success' => false,
                    'message' => 'البند غير موجود'
                ];
            }

            $task->items = $items;
            $task->save();

            return [
                'success' => true,
                'message' => 'تم تحديث البند بنجاح',
                'items' => $items
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update task item', [
                'task_id' => $task->id,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * حذف بند من المهمة
     */
    public function deleteTaskItem(Task $task, string $itemId): array
    {
        try {
            $items = $task->items ?? [];
            $items = array_filter($items, function($item) use ($itemId) {
                return $item['id'] !== $itemId;
            });

            // إعادة ترتيب البنود
            $items = array_values($items);
            foreach ($items as $index => &$item) {
                $item['order'] = $index + 1;
            }

            $task->items = $items;
            $task->save();

            return [
                'success' => true,
                'message' => 'تم حذف البند بنجاح',
                'items' => $items
            ];

        } catch (\Exception $e) {
            Log::error('Failed to delete task item', [
                'task_id' => $task->id,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف البند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * نسخ البنود من المهمة الأساسية إلى TaskUser عند التعيين
     */
    public function copyItemsToTaskUser(Task $task, TaskUser $taskUser): void
    {
        try {
            $taskItems = $task->items ?? [];

            if (empty($taskItems)) {
                Log::info('No items to copy to task user', [
                    'task_id' => $task->id,
                    'task_user_id' => $taskUser->id
                ]);
                return;
            }

            // نسخ البنود مع إضافة حالة افتراضية
            $userItems = array_map(function($item, $index) {
                return [
                    'id' => $item['id'] ?? Str::uuid()->toString(), // ✅ إنشاء ID تلقائي للبنود القديمة
                    'title' => $item['title'] ?? 'بند ' . ($index + 1),
                    'description' => $item['description'] ?? null,
                    'order' => $item['order'] ?? ($index + 1),
                    'status' => 'pending', // الحالة الافتراضية
                    'note' => null,
                    'completed_at' => null,
                    'completed_by' => null
                ];
            }, $taskItems, array_keys($taskItems));

            $taskUser->items = $userItems;
            $taskUser->save();

            Log::info('Copied items to task user', [
                'task_id' => $task->id,
                'task_user_id' => $taskUser->id,
                'items_count' => count($userItems)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to copy items to task user', [
                'task_id' => $task->id,
                'task_user_id' => $taskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * نسخ البنود من مهمة القالب إلى TemplateTaskUser عند التعيين
     */
    public function copyItemsToTemplateTaskUser(TemplateTask $templateTask, TemplateTaskUser $templateTaskUser): void
    {
        try {
            $templateItems = $templateTask->items ?? [];

            if (empty($templateItems)) {
                Log::info('No items to copy to template task user', [
                    'template_task_id' => $templateTask->id,
                    'template_task_user_id' => $templateTaskUser->id
                ]);
                return;
            }

            // نسخ البنود مع إضافة حالة افتراضية
            $userItems = array_map(function($item, $index) {
                return [
                    'id' => $item['id'] ?? Str::uuid()->toString(), // ✅ إنشاء ID تلقائي للبنود القديمة
                    'title' => $item['title'] ?? 'بند ' . ($index + 1),
                    'description' => $item['description'] ?? null,
                    'order' => $item['order'] ?? ($index + 1),
                    'status' => 'pending',
                    'note' => null,
                    'completed_at' => null,
                    'completed_by' => null
                ];
            }, $templateItems, array_keys($templateItems));

            $templateTaskUser->items = $userItems;
            $templateTaskUser->save();

            Log::info('Copied items to template task user', [
                'template_task_id' => $templateTask->id,
                'template_task_user_id' => $templateTaskUser->id,
                'items_count' => count($userItems)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to copy items to template task user', [
                'template_task_id' => $templateTask->id,
                'template_task_user_id' => $templateTaskUser->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * تحديث حالة بند للمستخدم (تم/لم يتم/لا ينطبق)
     */
    public function updateTaskUserItemStatus(TaskUser $taskUser, string $itemId, string $status, ?string $note = null): array
    {
        try {
            if (!in_array($status, ['pending', 'completed', 'not_applicable'])) {
                return [
                    'success' => false,
                    'message' => 'حالة البند غير صحيحة'
                ];
            }

            $items = $taskUser->items ?? [];
            $itemFound = false;

            foreach ($items as &$item) {
                if ($item['id'] === $itemId) {
                    $item['status'] = $status;
                    $item['note'] = $note;
                    $item['completed_at'] = ($status !== 'pending') ? $this->getCurrentCairoTime()->toDateTimeString() : null;
                    $item['completed_by'] = ($status !== 'pending') ? $taskUser->user_id : null;
                    $itemFound = true;
                    break;
                }
            }

            if (!$itemFound) {
                return [
                    'success' => false,
                    'message' => 'البند غير موجود'
                ];
            }

            $taskUser->items = $items;
            $taskUser->save();

            Log::info('Updated task user item status', [
                'task_user_id' => $taskUser->id,
                'item_id' => $itemId,
                'status' => $status
            ]);

            return [
                'success' => true,
                'message' => 'تم تحديث حالة البند بنجاح',
                'items' => $items,
                'progress' => $this->calculateTaskProgress($taskUser)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update task user item status', [
                'task_user_id' => $taskUser->id,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة البند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تحديث حالة بند لمستخدم مهمة القالب
     */
    public function updateTemplateTaskUserItemStatus(TemplateTaskUser $templateTaskUser, string $itemId, string $status, ?string $note = null): array
    {
        try {
            if (!in_array($status, ['pending', 'completed', 'not_applicable'])) {
                return [
                    'success' => false,
                    'message' => 'حالة البند غير صحيحة'
                ];
            }

            $items = $templateTaskUser->items ?? [];
            $itemFound = false;

            foreach ($items as &$item) {
                if ($item['id'] === $itemId) {
                    $item['status'] = $status;
                    $item['note'] = $note;
                    $item['completed_at'] = ($status !== 'pending') ? $this->getCurrentCairoTime()->toDateTimeString() : null;
                    $item['completed_by'] = ($status !== 'pending') ? $templateTaskUser->user_id : null;
                    $itemFound = true;
                    break;
                }
            }

            if (!$itemFound) {
                return [
                    'success' => false,
                    'message' => 'البند غير موجود'
                ];
            }

            $templateTaskUser->items = $items;
            $templateTaskUser->save();

            Log::info('Updated template task user item status', [
                'template_task_user_id' => $templateTaskUser->id,
                'item_id' => $itemId,
                'status' => $status
            ]);

            return [
                'success' => true,
                'message' => 'تم تحديث حالة البند بنجاح',
                'items' => $items,
                'progress' => $this->calculateTemplateTaskProgress($templateTaskUser)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update template task user item status', [
                'template_task_user_id' => $templateTaskUser->id,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة البند: ' . $e->getMessage()
            ];
        }
    }

    /**
     * حساب نسبة إنجاز البنود للمهمة
     */
    public function calculateTaskProgress(TaskUser $taskUser): array
    {
        $items = $taskUser->items ?? [];

        if (empty($items)) {
            return [
                'total' => 0,
                'completed' => 0,
                'pending' => 0,
                'not_applicable' => 0,
                'percentage' => 0
            ];
        }

        $total = count($items);
        $completed = 0;
        $pending = 0;
        $notApplicable = 0;

        foreach ($items as $item) {
            switch ($item['status'] ?? 'pending') {
                case 'completed':
                    $completed++;
                    break;
                case 'not_applicable':
                    $notApplicable++;
                    break;
                default:
                    $pending++;
            }
        }

        // حساب النسبة: البنود المكتملة + البنود التي لا تنطبق
        $done = $completed + $notApplicable;
        $percentage = ($total > 0) ? round(($done / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'not_applicable' => $notApplicable,
            'percentage' => $percentage
        ];
    }

    /**
     * حساب نسبة إنجاز البنود لمهمة القالب
     */
    public function calculateTemplateTaskProgress(TemplateTaskUser $templateTaskUser): array
    {
        $items = $templateTaskUser->items ?? [];

        if (empty($items)) {
            return [
                'total' => 0,
                'completed' => 0,
                'pending' => 0,
                'not_applicable' => 0,
                'percentage' => 0
            ];
        }

        $total = count($items);
        $completed = 0;
        $pending = 0;
        $notApplicable = 0;

        foreach ($items as $item) {
            switch ($item['status'] ?? 'pending') {
                case 'completed':
                    $completed++;
                    break;
                case 'not_applicable':
                    $notApplicable++;
                    break;
                default:
                    $pending++;
            }
        }

        $done = $completed + $notApplicable;
        $percentage = ($total > 0) ? round(($done / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'not_applicable' => $notApplicable,
            'percentage' => $percentage
        ];
    }
}

