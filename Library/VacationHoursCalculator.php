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

		// Get timestamp of now
		$endDate = new DateTime();

		// A one time addition of vacation hours
		// Can for example be used to 'transfer' someone's vaction over from a previous contract
		$initial_hours = $user->getPreferenceValue('start-of-period-vacation-hours', 0);

		// Total seconds in a year, not account for leap years
		// This is in favor of the employee, as they will accrue vacation hours slightly faster/more during leap years
		$year_length = 365 * 24 * 60 * 60;

		// Calculate rate at which vacation hours are accrued, in 'vacation hours' per second
		// And calculate how many 'additional' vacation hours have been accrued
		$additional_vacation_hours_per_second = $user->getPreferenceValue('yearly-vacation-hours') / $year_length;
		$earned_additional_vacation_hours = $seconds_elapsed * $additional_vacation_hours_per_second;

		// Calculate rate at which vacation hours are accrued, in 'vacation hours' per second
		// And calculate how many 'fixed' vacation hours have been accrued
		$fixed_vacation_hours_per_second = $user->getPreferenceValue('extra-vacation-hours') / $year_length;
		$earned_fixed_vacation_hours_per_second = $seconds_elapsed * $fixed_vacation_hours_per_second;
		
		// The grand total of vacation hours accrued
		$total_vacation_hours = $initial_hours + $earned_additional_vacation_hours + $earned_$fixed_vacation_hours_per_second;

		// Expected work hours based on weeks elapsed
		$week_length = 7 * 24 * 60 * 60;
		$elapsed_weeks = $seconds_elapsed / $week_length;
		$expected_worked_hours = $elapsed_weeks * $user->getPreferenceValue('target-weekly-hours', 0);
		$actual_worked_hours = $repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

		// Remaining vacation hours calculation
		$work_left = $expected_worked_hours - $actual_worked_hours;
		$vacation_hours_left = max(0, $total_vacation_hours-$work_left);

		return $vacation_hours_left;
	}

	public static function calculateHoursV1(User $user, TimeSheetRepository $repository)
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

	public static function calculateHoursOld(User $user, TimeSheetRepository $repository)
	{
		$accounting_start = strtotime($user->getPreferenceValue('target-weekly-start'));
		if ($accounting_start === false) return;
		$startDate = new DateTime();
		$startDate->setTimestamp($accounting_start);

		$seconds_elapsed = time() - $accounting_start;
		if ($seconds_elapsed < 0) return;
		$endDate = new DateTime();

		$fte_ratio = $user->getPreferenceValue('target-weekly-hours', 0) / 40.0;

		$leftover_hours = $user->getPreferenceValue('start-of-period-vacation-hours', 0);

		// Not accounting for leap years
		$year_length = 365 * 24 * 60 * 60;
		$vacation_hours_per_second = 8 * $user->getPreferenceValue('yearly-fte-vacation-days') / $year_length;

		$earned_hours = $fte_ratio * $seconds_elapsed * $vacation_hours_per_second;

		$total_vacation_hours = $leftover_hours + $earned_hours;

		$week_length = 7 * 24 * 60 * 60;
		$elapsed_weeks = $seconds_elapsed / $week_length;

		$expected_work_hours = $elapsed_weeks * $fte_ratio * 40;

		$worked_hours = $repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

		$work_left = $expected_work_hours - $total_vacation_hours - $worked_hours;
		$vacation_hours_left = max(0, -$work_left);

		# Instead of seconds, we return the time in hours, so that each calculation returns a value in the same unit
		#return (int)($vacation_hours_left * 60 * 60);
		return $vacation_hours_left;
	}

	public static function formatHours(float $time)
	{
		// Formatting as hours and minutes
		$hours = floor($time);
		$minutes = ($time - $hours) * 60;
		$formatted_vacation_hours = sprintf("%02d:%02d h", $hours, $minutes);
		return $formatted_vacation_hours;
	}
}
