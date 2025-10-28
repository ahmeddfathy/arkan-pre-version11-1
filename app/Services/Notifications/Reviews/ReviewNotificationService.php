<?php

namespace App\Services\Notifications\Reviews;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\Traits\HasFirebaseNotification;
use App\Services\Notifications\Traits\HasReviewSlackNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewNotificationService
{
    use HasFirebaseNotification, HasReviewSlackNotification;

    /**
     * Notify the HR team and the employee about a review creation/update
     *
     * @param mixed $review The review model instance
     * @param string $action The action performed (created, updated, deleted)
     * @param string $reviewType The type of review (technical, marketing, coordination, customer_service)
     * @return void
     */
    public function notifyReviewEvent($review, string $action, string $reviewType): void
    {
        try {
            // Get the employee (user) who received the review
            $employee = $review->user;

            // Get the reviewer who created the review
            $reviewer = $review->reviewer;

            if (!$employee || !$reviewer) {
                Log::error('Employee or reviewer not found for review', [
                    'review_id' => $review->id,
                    'review_type' => $reviewType
                ]);
                return;
            }

            // Format Arabic month name
            $monthYear = $review->review_month;
            list($year, $month) = explode('-', $monthYear);

            $arabicMonths = [
                '01' => 'يناير',
                '02' => 'فبراير',
                '03' => 'مارس',
                '04' => 'أبريل',
                '05' => 'مايو',
                '06' => 'يونيو',
                '07' => 'يوليو',
                '08' => 'أغسطس',
                '09' => 'سبتمبر',
                '10' => 'أكتوبر',
                '11' => 'نوفمبر',
                '12' => 'ديسمبر'
            ];

            $arabicMonth = $arabicMonths[$month] ?? $month;
            $formattedDate = $arabicMonth . ' ' . $year;

            // Format the review type in Arabic
            $reviewTypeArabic = [
                'technical' => 'التقني',
                'marketing' => 'التسويق',
                'coordination' => 'التنسيق',
                'customer_service' => 'خدمة العملاء'
            ][$reviewType] ?? $reviewType;

            // Format the action in Arabic
            $actionArabic = [
                'created' => 'إضافة',
                'updated' => 'تحديث',
                'deleted' => 'حذف'
            ][$action] ?? $action;

            // Create notification for the employee
            $this->notifyEmployee($review, $employee, $actionArabic, $reviewTypeArabic, $formattedDate);

            // Notify HR team
            $this->notifyHRTeam($review, $employee, $reviewer, $actionArabic, $reviewTypeArabic, $formattedDate);

        } catch (\Exception $e) {
            Log::error('Error in ReviewNotificationService::notifyReviewEvent', [
                'error' => $e->getMessage(),
                'review_id' => $review->id ?? 'unknown',
                'review_type' => $reviewType,
                'action' => $action
            ]);
        }
    }

    /**
     * Notify the employee about their review
     */
    private function notifyEmployee($review, User $employee, string $actionArabic, string $reviewTypeArabic, string $formattedDate): void
    {
        try {
            // Skip if the employee is the current user (they created their own review)
            if ($employee->id === Auth::id()) {
                return;
            }

            $message = "تم {$actionArabic} تقييم {$reviewTypeArabic} الخاص بك لشهر {$formattedDate}";

            // Create notification record
            $notification = Notification::create([
                'user_id' => $employee->id,
                'type' => 'review_' . strtolower($actionArabic),
                'data' => [
                    'message' => $message,
                    'review_id' => $review->id,
                    'review_type' => $reviewTypeArabic,
                    'review_month' => $formattedDate,
                    'total_score' => $this->calculateTotalScore($review),
                    'notification_time' => now()->format('Y-m-d H:i:s')
                ],
                'related_id' => $review->id
            ]);

            // Send Firebase notification if token exists
            if ($employee->fcm_token) {
                $actionType = strtolower($actionArabic);
                $this->sendTypedFirebaseNotification($employee, 'review', $actionType, $message, $review->id);
            }
        } catch (\Exception $e) {
            Log::error('Error notifying employee about review', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'review_id' => $review->id
            ]);
        }
    }

    /**
     * Notify the HR team about the review
     */
    private function notifyHRTeam($review, User $employee, User $reviewer, string $actionArabic, string $reviewTypeArabic, string $formattedDate): void
    {
        try {
            // Get all HR team members
            $hrUsers = User::role('hr')->where('id', '!=', Auth::id())->get();

            if ($hrUsers->isEmpty()) {
                Log::info('No HR users found to notify about review');
                return;
            }

            $message = "قام {$reviewer->name} بـ{$actionArabic} تقييم {$reviewTypeArabic} للموظف {$employee->name} لشهر {$formattedDate}";

            foreach ($hrUsers as $hrUser) {
                // Create notification record
                $notification = Notification::create([
                    'user_id' => $hrUser->id,
                    'type' => 'hr_review_' . strtolower($actionArabic),
                    'data' => [
                        'message' => $message,
                        'review_id' => $review->id,
                        'employee_name' => $employee->name,
                        'reviewer_name' => $reviewer->name,
                        'review_type' => $reviewTypeArabic,
                        'review_month' => $formattedDate,
                        'total_score' => $this->calculateTotalScore($review),
                        'notification_time' => now()->format('Y-m-d H:i:s')
                    ],
                    'related_id' => $review->id
                ]);

                // Send Firebase notification if token exists
                if ($hrUser->fcm_token) {
                    $actionType = strtolower($actionArabic);
                    $this->sendTypedFirebaseNotification($hrUser, 'review', $actionType, $message, $review->id);
                }
            }

            // Send Slack notification to HR channel with additional data
            $slackMessage = "{$actionArabic} تقييم: {$employee->name} - {$reviewTypeArabic} - {$formattedDate}";

            $additionalData = [
                'المراجع' => $reviewer->name,
                'الدرجة الكلية' => $this->calculateTotalScore($review),
                'الشهر' => $formattedDate
            ];

            $this->setReviewNotificationContext('إشعار تقييم الموظف');
            $this->sendReviewSlackNotification($slackMessage, $actionArabic, $reviewTypeArabic, $additionalData);

        } catch (\Exception $e) {
            Log::error('Error notifying HR team about review', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'reviewer_id' => $reviewer->id,
                'review_id' => $review->id
            ]);
        }
    }

    /**
     * Calculate total score for a review
     */
    private function calculateTotalScore($review): int
    {
        try {
            // Get all attributes from the review
            $attributes = $review->getAttributes();

            $totalScore = 0;
            $scoreAttributes = [];

            // Find all score attributes (fields ending with _score)
            foreach ($attributes as $key => $value) {
                if (strpos($key, '_score') !== false && $key !== 'total_score') {
                    $scoreAttributes[$key] = (int)$value;
                }
            }

            // Find all penalty attributes (fields ending with _penalty)
            $penaltyAttributes = [];
            foreach ($attributes as $key => $value) {
                if (strpos($key, '_penalty') !== false) {
                    $penaltyAttributes[$key] = (int)$value;
                }
            }

            // Calculate total score (sum of scores minus sum of penalties)
            $totalScore = array_sum($scoreAttributes) - array_sum($penaltyAttributes);

            return max(0, $totalScore); // Ensure score is not negative
        } catch (\Exception $e) {
            Log::error('Error calculating total score', [
                'error' => $e->getMessage(),
                'review_id' => $review->id ?? 'unknown'
            ]);
            return 0;
        }
    }
}
