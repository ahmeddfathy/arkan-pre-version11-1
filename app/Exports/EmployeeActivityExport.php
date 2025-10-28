<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeActivityExport implements FromArray, WithHeadings, WithMapping
{
    protected $entries;
    protected $employeeId;
    protected $date;

    public function __construct($entries, $employeeId, $date)
    {
        $this->entries = $entries;
        $this->employeeId = $employeeId;
        $this->date = $date;
    }

    public function array(): array
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            'Time Block',
            'Status',
            'Mouse Clicks',
            'Keyboard Clicks',
            'Active Seconds',
            'Last Window Title'
        ];
    }

    public function map($entry): array
    {
        $activeSeconds = 0;
        if (isset($entry['total_active_seconds'])) {
            $activeSeconds = $entry['total_active_seconds'];
        } elseif (isset($entry['active_seconds'])) {
            $activeSeconds = $entry['active_seconds'];
        } elseif (isset($entry['active_seconds_in_minute'])) {
            $activeSeconds = $entry['active_seconds_in_minute'];
        } elseif (isset($entry['status']) && $entry['status'] === 'ACTIVE') {
            $activeSeconds = 60;
        }

        return [
            $entry['time_block'] ?? '',
            $entry['status'] ?? '',
            $entry['mouse_clicks'] ?? 0,
            $entry['keyboard_clicks'] ?? 0,
            $activeSeconds,
            $entry['last_window_title'] ?? ''
        ];
    }
}
