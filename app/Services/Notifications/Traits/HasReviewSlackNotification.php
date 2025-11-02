<?php

namespace App\Services\Notifications\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HasReviewSlackNotification
{
    protected function sendReviewSlackNotification(string $message, string $actionType = 'إضافة', string $reviewType = '', array $additionalData = []): bool
    {
        try {
            $reviewsWebhookUrl = env('SLACK_REVIEW_WEBHOOK_URL', env('SLACK_WEBHOOK_URL'));

            if (empty($reviewsWebhookUrl)) {
                $this->setReviewNotificationStatus(false, 'Reviews webhook URL not configured');
                return false;
            }

            $color = '#36a64f';
            $operationIcon = ':white_check_mark:';

            if ($actionType === 'تحديث') {
                $color = '#3AA3E3';
                $operationIcon = ':arrows_counterclockwise:';
            } elseif ($actionType === 'حذف') {
                $color = '#E01E5A';
                $operationIcon = ':x:';
            }

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

            $headerText = "*{$reviewIcon} تقييمات الأداء - {$operationIcon} {$actionType}*";

            $fields = [];
            foreach ($additionalData as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $fields[] = [
                        'type' => 'mrkdwn',
                        'text' => "*{$key}:* {$value}"
                    ];
                }
            }

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

            $payload = [
                'text' => "تقييمات الأداء: {$message}",
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

            $response = Http::timeout(3)->post($reviewsWebhookUrl, $payload);

            $success = $response->successful();
            $this->setReviewNotificationStatus($success, $success ? 'Review notification sent to HR' : 'Failed to send review notification');

            return $success;

        } catch (\Exception $e) {           
            Log::warning('Review HR Slack timeout or error - continuing anyway', ['error' => $e->getMessage()]);
            $this->setReviewNotificationStatus(true, 'تم المحاولة (انتهت مهلة الانتظار)');
            return true;
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
