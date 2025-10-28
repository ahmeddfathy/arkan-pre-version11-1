<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\ProjectDelivery;
use App\Models\ProjectServiceUser;
use App\Services\Notifications\DeliveryNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectDeliveryService
{
    protected $deliveryNotificationService;

    public function __construct(DeliveryNotificationService $deliveryNotificationService)
    {
        $this->deliveryNotificationService = $deliveryNotificationService;
    }
    /**
     * تسليم مشروع (مسودة أو نهائي)
     */
    public function deliverProject(Project $project, string $deliveryType, ?string $notes = null): array
    {
        try {
            DB::beginTransaction();

            $delivery = ProjectDelivery::create([
                'project_id' => $project->id,
                'delivery_type' => $deliveryType,
                'delivery_date' => now(),
                'delivered_by' => Auth::id(),
                'notes' => $notes,
            ]);

            Log::info("Project delivered", [
                'project_id' => $project->id,
                'delivery_type' => $deliveryType,
                'delivered_by' => Auth::id()
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تسليم المشروع بنجاح.',
                'delivery' => $delivery
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delivering project', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تسليم المشروع: ' . $e->getMessage()
            ];
        }
    }

    /**
     * البحث عن المشاريع مع تسليماتها
     */
    public function searchProjects(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Project::query();

        // البحث بالاسم أو الكود
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // فلتر حسب الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // فلتر حسب العميل
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // فلتر حسب نوع التسليم
        if (!empty($filters['delivery_type'])) {
            $query->whereHas('deliveries', function($q) use ($filters) {
                $q->where('delivery_type', $filters['delivery_type']);
            });
        }

        // فلتر حسب تاريخ التسليم
        if (!empty($filters['delivery_date_from'])) {
            $query->whereHas('deliveries', function($q) use ($filters) {
                $q->where('delivery_date', '>=', $filters['delivery_date_from']);
            });
        }
        if (!empty($filters['delivery_date_to'])) {
            $query->whereHas('deliveries', function($q) use ($filters) {
                $q->where('delivery_date', '<=', $filters['delivery_date_to']);
            });
        }

        // الترتيب
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // تحميل العلاقات المطلوبة
        return $query->with([
            'client',
            'deliveries.deliveredBy',
            'lastDraftDelivery',
            'lastFinalDelivery'
        ])->paginate(15);
    }

    /**
     * الحصول على إحصائيات التسليمات
     */
    public function getDeliveriesStats(): array
    {
        $totalDeliveries = ProjectDelivery::count();
        $draftDeliveries = ProjectDelivery::where('delivery_type', 'مسودة')->count();
        $finalDeliveries = ProjectDelivery::where('delivery_type', 'نهائي')->count();

        $projectsWithDraft = Project::whereHas('deliveries', function($q) {
            $q->where('delivery_type', 'مسودة');
        })->count();

        $projectsWithFinal = Project::whereHas('deliveries', function($q) {
            $q->where('delivery_type', 'نهائي');
        })->count();

        return [
            'total_deliveries' => $totalDeliveries,
            'draft_deliveries' => $draftDeliveries,
            'final_deliveries' => $finalDeliveries,
            'projects_with_draft' => $projectsWithDraft,
            'projects_with_final' => $projectsWithFinal,
        ];
    }

    /**
     * حذف تسليم
     */
    public function deleteDelivery(ProjectDelivery $delivery): array
    {
        try {
            $delivery->delete();

            Log::info("Delivery deleted", [
                'delivery_id' => $delivery->id,
                'project_id' => $delivery->project_id
            ]);

            return [
                'success' => true,
                'message' => 'تم حذف التسليم بنجاح.'
            ];

        } catch (\Exception $e) {
            Log::error('Error deleting delivery', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التسليم: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تسليم مشروع من قبل المشارك
     */
    public function deliverParticipantProject($participantId): array
    {
        try {
            DB::beginTransaction();

            $participant = ProjectServiceUser::findOrFail($participantId);

            // التحقق من أن المستخدم الحالي هو صاحب المشروع أو لديه صلاحية
            if ($participant->user_id !== Auth::id()) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بتسليم هذا المشروع',
                    'status_code' => 403
                ];
            }

            // التحقق من أن المشروع لم يتم تسليمه مسبقاً
            if ($participant->isDelivered()) {
                return [
                    'success' => false,
                    'message' => 'تم تسليم هذا المشروع مسبقاً',
                    'status_code' => 400
                ];
            }

            // التحقق من أن المشروع لم يتم اعتماده مسبقاً
            if ($participant->hasAdministrativeApproval() || $participant->hasTechnicalApproval()) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إعادة تسليم مشروع تم اعتماده مسبقاً',
                    'status_code' => 400
                ];
            }

            // تسليم المشروع
            $participant->deliver();

            // إرسال الإشعارات للمعتمدين
            try {
                $this->deliveryNotificationService->notifyApproversWhenDelivered($participant);
            } catch (\Exception $e) {
                Log::warning('Failed to send delivery notifications', [
                    'participant_id' => $participantId,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info("Participant project delivered", [
                'participant_id' => $participantId,
                'project_id' => $participant->project_id,
                'user_id' => $participant->user_id,
                'service_id' => $participant->service_id
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تسليم المشروع بنجاح',
                'status_code' => 200,
                'participant' => $participant
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delivering participant project', [
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تسليم المشروع: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    /**
     * إلغاء تسليم مشروع من قبل المشارك (إعادة التعيين)
     */
    public function undeliverParticipantProject($participantId): array
    {
        try {
            DB::beginTransaction();

            $participant = ProjectServiceUser::findOrFail($participantId);

            // التحقق من أن المستخدم الحالي هو صاحب المشروع أو لديه صلاحية
            if ($participant->user_id !== Auth::id()) {
                return [
                    'success' => false,
                    'message' => 'غير مسموح لك بإلغاء تسليم هذا المشروع',
                    'status_code' => 403
                ];
            }

            // التحقق من أن المشروع تم تسليمه مسبقاً
            if (!$participant->isDelivered()) {
                return [
                    'success' => false,
                    'message' => 'لم يتم تسليم هذا المشروع مسبقاً',
                    'status_code' => 400
                ];
            }

            // التحقق من أنه يمكن إلغاء التسليم
            if (!$participant->canBeUndelivered()) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إلغاء تسليم مشروع تم اعتماده مسبقاً',
                    'status_code' => 400
                ];
            }

            // إلغاء تسليم المشروع
            $participant->undeliver();

            // إرسال الإشعارات للمعتمدين
            try {
                $this->deliveryNotificationService->notifyApproversWhenUndelivered($participant);
            } catch (\Exception $e) {
                Log::warning('Failed to send undelivery notifications', [
                    'participant_id' => $participantId,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info("Participant project undelivered", [
                'participant_id' => $participantId,
                'project_id' => $participant->project_id,
                'user_id' => $participant->user_id,
                'service_id' => $participant->service_id
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم إلغاء تسليم المشروع بنجاح',
                'status_code' => 200,
                'participant' => $participant
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error undelivering participant project', [
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء تسليم المشروع: ' . $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}
