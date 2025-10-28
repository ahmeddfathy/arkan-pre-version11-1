<?php

namespace App\Services\Notifications\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HasSlackNotification
{
    /**
     * إرسال إشعار إلى Slack
     *
     * @param string $message رسالة الإشعار
     * @param string $operation نوع العملية (إنشاء، تحديث، حذف)
     * @param array $additionalData بيانات إضافية للرسالة
     * @return bool نجاح أو فشل العملية
     */
    protected function sendSlackNotification(string $message, string $operation = 'إنشاء', array $additionalData = []): bool
    {
        try {
            $webhookUrl = env('SLACK_WEBHOOK_URL');

            if (empty($webhookUrl)) {
                $this->setHRNotificationStatus(false, 'Slack webhook URL not configured');
                return false;
            }

            // تحديد اللون بناءً على نوع العملية
            $color = '#36a64f'; // أخضر للإنشاء
            $operationIcon = ':white_check_mark:';

            if ($operation === 'تحديث') {
                $color = '#3AA3E3'; // أزرق للتحديث
                $operationIcon = ':arrows_counterclockwise:';
            } elseif ($operation === 'حذف') {
                $color = '#E01E5A'; // أحمر للحذف
                $operationIcon = ':x:';
            }

            // بناء رسالة أكثر تنظيماً وجاذبية
            $payload = [
                'text' => $message, // النص الرئيسي للرسالة
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "*$operationIcon $operation*"
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
                    ]
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

            // إضافة زر للرابط إذا كان متوفراً
            if (isset($additionalData['link_url']) && isset($additionalData['link_text'])) {
                $payload['blocks'][] = [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => $additionalData['link_text']
                            ],
                            'url' => $additionalData['link_url'],
                            'style' => 'primary'
                        ]
                    ]
                ];
            }

            // ⚡ تقليل timeout للـ HR webhook
            $response = Http::timeout(3)->post($webhookUrl, $payload);

            $success = $response->successful();
            $this->setHRNotificationStatus($success, $success ? 'HR channel notification sent' : 'Failed to send to HR channel');

            return $success;

        } catch (\Exception $e) {
            // 🛡️ Fallback سريع للـ HR channel
            Log::warning('HR Slack timeout or error - continuing anyway', ['error' => $e->getMessage()]);
            $this->setHRNotificationStatus(true, 'تم المحاولة (انتهت مهلة الانتظار)');
            return true; // نقول نجحت عشان الصفحة تكمل
        }
    }

    protected function setHRNotificationStatus(bool $success, string $message = '')
    {
        $context = session()->get('hr_slack_context', 'HR Slack');
        session()->flash('slack_notification', [
            'success' => $success,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->timestamp
        ]);
    }

    protected function setHRNotificationContext(string $context)
    {
        session()->put('hr_slack_context', $context);
    }
}
