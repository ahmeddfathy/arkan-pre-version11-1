<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class EmployeeBirthdayController extends Controller
{
    public function index()
    {
        // Check if user has HR role
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if (!$authUser->hasRole('hr')) {
            abort(403, 'Unauthorized action.');
        }

        // Get all active employees with date_of_birth
        $employees = User::whereNotNull('date_of_birth')
            ->where('employee_status', 'active')
            ->get();

        $today = Carbon::today();
        $employeesWithBirthdayData = [];

        foreach ($employees as $employee) {
            // Skip if date_of_birth is null
            if (!$employee->date_of_birth) {
                continue;
            }

            // Calculate age
            $birthDate = Carbon::parse($employee->date_of_birth);
            $age = $birthDate->age;

            // Calculate next birthday
            $nextBirthday = Carbon::parse($employee->date_of_birth)->setYear($today->year);

            // If birthday has already passed this year, set it to next year
            if ($nextBirthday->isPast()) {
                $nextBirthday->addYear();
            }

            // Calculate days until next birthday
            $daysUntilBirthday = $today->diffInDays($nextBirthday, false);
            if ($daysUntilBirthday < 0) {
                $daysUntilBirthday += 365;
            }

            // Check if the birthday is in the current week
            $isInSameWeek = $nextBirthday->diffInDays($today) < 7 && $nextBirthday->diffInDays($today, false) >= 0;

            $employeesWithBirthdayData[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'birth_date' => $birthDate->format('Y-m-d'),
                'age' => $age,
                'next_birthday' => $nextBirthday->format('Y-m-d'),
                'days_until_birthday' => $daysUntilBirthday,
                'is_in_same_week' => $isInSameWeek,
            ];
        }

        // Sort employees by days until birthday (closest first)
        usort($employeesWithBirthdayData, function ($a, $b) {
            return $a['days_until_birthday'] - $b['days_until_birthday'];
        });

        return view('employees-brithday.birthdays', compact('employeesWithBirthdayData'));
    }
}
