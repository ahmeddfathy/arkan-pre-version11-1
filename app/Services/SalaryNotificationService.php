<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class SalaryNotificationService
{
    public function createSalarySheetNotification(User $user, array $salarySheetData): void
    {
        Notification::create([
            'user_id' => $user->id,
            'type' => 'salary_sheet_uploaded',
            'data' => [
                'message' => "Salary sheet for {$salarySheetData['month']} has been uploaded.",
                'month' => $salarySheetData['month'],
                'file_name' => $salarySheetData['filename'],
            ],
            'related_id' => $salarySheetData['id']
        ]);
    }

    public function getUserUnreadNotifications(int $userId): array
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->markAsRead();
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
