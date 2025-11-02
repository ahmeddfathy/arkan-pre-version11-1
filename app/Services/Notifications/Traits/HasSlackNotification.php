<?php

namespace App\Services\Notifications\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait HasSlackNotification
{
    protected function sendSlackNotification(string $message, string $operation = 'إنشاء', array $additionalData = []): bool
    {
        try {
            $webhookUrl = env('SLACK_WEBHOOK_URL');

            if (empty($webhookUrl)) {
                $this->setHRNotificationStatus(false, 'Slack webhook URL not configured');
                return false;
            }

            $color = '#36a64f';
            $operationIcon = ':white_check_mark:';

            if ($operation === 'تحديث') {
                $color = '#3AA3E3';
                $operationIcon = ':arrows_counterclockwise:';
            } elseif ($operation === 'حذف') {
                $color = '#E01E5A';
                $operationIcon = ':x:';
            }

            $payload = [
                'text' => $message,
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

            $response = Http::timeout(3)->post($webhookUrl, $payload);

            $success = $response->successful();
            $this->setHRNotificationStatus($success, $success ? 'HR channel notification sent' : 'Failed to send to HR channel');

            return $success;

        } catch (\Exception $e) {       
            Log::warning('HR Slack timeout or error - continuing anyway', ['error' => $e->getMessage()]);
            $this->setHRNotificationStatus(true, 'تم المحاولة (انتهت مهلة الانتظار)');
            return true;
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
