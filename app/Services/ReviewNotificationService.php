<?php

namespace App\Services;

use App\Services\Notifications\Reviews\ReviewNotificationService as NotificationService;
use Illuminate\Support\Facades\Log;

class ReviewNotificationService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }



}
