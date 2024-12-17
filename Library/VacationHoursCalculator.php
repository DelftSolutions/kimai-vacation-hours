<?php

namespace KimaiPlugin\VacationHoursBundle\Library;

use App\Entity\User;
use App\Repository\TimeSheetRepository;
use DateTime;

class VacationHoursCalculator
{
	public static function calculateHours(User $user, TimeSheetRepository $repository)
	{
		$accounting_start = strtotime($user->getPreferenceValue('target-weekly-start'));
		if ($accounting_start === false)
			return null;

		$startDate = new DateTime();
		$startDate->setTimestamp($accounting_start);
		$seconds_elapsed = time() - $accounting_start;
		if ($seconds_elapsed < 0)
			return null;
		$endDate = new DateTime();

		// for 32h per week, this will be 0.8
		$fte_ratio = $user->getPreferenceValue('target-weekly-hours', 0) / 40.0;

		// Leftover vacation hours from previous periods
		$leftover_hours = $user->getPreferenceValue('start-of-period-vacation-hours', 0);

		// Total seconds in a year, not account for leap years
		$year_length = 365 * 24 * 60 * 60;

		// Calculate accrued vacation hours per second
		$vacation_hours_per_second = $user->getPreferenceValue('yearly-vacation-hours') / $year_length;

		// Earned vacation hours based on elapsed time
		$earned_hours = $seconds_elapsed * $vacation_hours_per_second;

		$extra_vacation_hours = $user->getPreferenceValue('extra-vacation-hours');
		$total_vacation_hours = $leftover_hours + $earned_hours + $extra_vacation_hours;

		// Expected work hours based on weeks elapsed
		$week_length = 7 * 24 * 60 * 60;
		$elapsed_weeks = $seconds_elapsed / $week_length;
		$expected_work_hours = $elapsed_weeks * $fte_ratio * 40;
		$worked_hours = $repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

		// Remaining vacation hours calculation
		$work_left = $expected_work_hours - $total_vacation_hours - $worked_hours;
		$vacation_hours_left = max(0, -$work_left);

		return $vacation_hours_left;
	}
}
