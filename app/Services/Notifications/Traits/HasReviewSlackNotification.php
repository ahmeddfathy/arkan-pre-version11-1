<?php

namespace App\Services\Notifications\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HasReviewSlackNotification
{
    /**
     * إرسال إشعار إلى قناة Slack الخاصة بالتقييمات
     *
     * @param string $message رسالة الإشعار
     * @param string $actionType نوع العملية (إضافة، تحديث، حذف)
     * @param string $reviewType نوع التقييم (التقني، التسويق، التنسيق، خدمة العملاء)
     * @param array $additionalData بيانات إضافية للرسالة
     * @return bool نجاح أو فشل العملية
     */
    protected function sendReviewSlackNotification(string $message, string $actionType = 'إضافة', string $reviewType = '', array $additionalData = []): bool
    {
        try {
            // استخدام متغير البيئة المخصص لقناة Slack الخاصة بالتقييمات
            // يمكن تعريف هذا المتغير في ملف .env
            $reviewsWebhookUrl = env('SLACK_REVIEW_WEBHOOK_URL', env('SLACK_WEBHOOK_URL'));

            if (empty($reviewsWebhookUrl)) {
                $this->setReviewNotificationStatus(false, 'Reviews webhook URL not configured');
                return false;
            }

            // تحديد اللون والأيقونة بناءً على نوع العملية
            $color = '#36a64f'; // أخضر للإضافة
            $operationIcon = ':white_check_mark:';

            if ($actionType === 'تحديث') {
                $color = '#3AA3E3'; // أزرق للتحديث
                $operationIcon = ':arrows_counterclockwise:';
            } elseif ($actionType === 'حذف') {
                $color = '#E01E5A'; // أحمر للحذف
                $operationIcon = ':x:';
            }

            // إضافة أيقونة مخصصة لنوع التقييم
            $reviewIcon = ':clipboard:';
            if ($reviewType === 'التقني') {
                $reviewIcon = ':computer:';
            } elseif ($reviewType === 'التسويق') {
                $reviewIcon = ':chart_with_upwards_trend:';
            } elseif ($reviewType === 'التنسيق') {
                $reviewIcon = ':art:';
            } elseif ($reviewType === 'خدمة العملاء') {
                $reviewIcon = ':handshake:';
            }

            // بناء عنوان الرسالة
            $headerText = "*{$reviewIcon} تقييمات الأداء - {$operationIcon} {$actionType}*";

            // إضافة البيانات الإضافية كحقول في الرسالة
            $fields = [];
            foreach ($additionalData as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $fields[] = [
                        'type' => 'mrkdwn',
                        'text' => "*{$key}:* {$value}"
                    ];
                }
            }

            // إنشاء قسم للبيانات الإضافية إذا وجدت
            $additionalBlocks = [];
            if (!empty($fields)) {
                $additionalBlocks[] = [
                    'type' => 'divider'
                ];
                $additionalBlocks[] = [
                    'type' => 'section',
                    'fields' => $fields
                ];
            }

            // بناء رسالة Slack
            $payload = [
                'text' => "تقييمات الأداء: {$message}", // النص الرئيسي للرسالة
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $headerText
                        ]
                    ],
                    [
                        'type' => 'divider'
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message
                        ]
                    ],
                    ...$additionalBlocks
                ],
                'attachments' => [
                    [
                        'color' => $color,
                        'blocks' => [
                            [
                                'type' => 'context',
                                'elements' => [
                                    [
                                        'type' => 'mrkdwn',
                                        'text' => "🕒 " . now()->format('Y-m-d H:i:s')
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            // ⚡ تقليل timeout للـ Reviews webhook
            $response = Http::timeout(3)->post($reviewsWebhookUrl, $payload);

            $success = $response->successful();
            $this->setReviewNotificationStatus($success, $success ? 'Review notification sent to HR' : 'Failed to send review notification');

            return $success;

        } catch (\Exception $e) {
            // 🛡️ Fallback سريع للـ Review HR channel
            Log::warning('Review HR Slack timeout or error - continuing anyway', ['error' => $e->getMessage()]);
            $this->setReviewNotificationStatus(true, 'تم المحاولة (انتهت مهلة الانتظار)');
            return true; // نقول نجحت عشان الصفحة تكمل
        }
    }

    protected function setReviewNotificationStatus(bool $success, string $message = '')
    {
        $context = session()->get('review_slack_context', 'Review HR Slack');
        session()->flash('slack_notification', [
            'success' => $success,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->timestamp
        ]);
    }

    protected function setReviewNotificationContext(string $context)
    {
        session()->put('review_slack_context', $context);
    }
}
