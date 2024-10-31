<?php

namespace KimaiPlugin\VacationHoursBundle\Widget;

use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractWidget;
use DateTime;
use DateInterval;
// use App\Security\CurrentUser;
use App\Widget\Type\SimpleWidget;
use App\Widget\WidgetInterface;

final class VacationWidget extends AbstractWidget
{
	public function __construct(private TimesheetRepository $repository)
	{
		// $user = $this->getUser();
		// $this->repository = $repository;


		// $this->setId('VacationWidget');
		// $this->setTitle('vacation hours left');
		// $this->getOptions([
		// 	'user' => $user->getUser(),
		// 	'id' => '',
		// 'icon' => 'time',
		// 	'dataType' => 'duration',
		// ]);
	}

	public function getWidth(): int
	{
		return WidgetInterface::WIDTH_SMALL;
	}

	public function getHeight(): int
	{
		return WidgetInterface::HEIGHT_SMALL;
	}

	public function getOptions(array $options = []): array
	{
		$options = parent::getOptions($options);
		$options['icon'] = 'spinner';
		if (empty($options['id'])) {
			$options['id'] = 'VacationWidget';
		}

		return $options;
	}

	public function getData(array $options = []): mixed
	{

		$options = $this->getOptions($options);
		$user = $this->getUser();
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
		$leftover_hours = $user->getPreferenceValue('start-of-period-vacation-hours', 0);

		// Calculate vacation accrual per second in hours
		$year_length = 365 * 24 * 60 * 60; // Total seconds in a year
		$vacation_hours_per_second = ($fte_ratio * $user->getPreferenceValue('yearly-fte-vacation-hours') * 8) / $year_length;

		// Earned hours based on time elapsed

		$earned_hours = $seconds_elapsed * $vacation_hours_per_second;
		$total_vacation_hours = $leftover_hours + $earned_hours;

		$week_length = 7 * 24 * 60 * 60;
		$elapsed_weeks = $seconds_elapsed / $week_length;
		$expected_work_hours = $elapsed_weeks * $fte_ratio * 40;
		$worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

		$work_left = $expected_work_hours - $total_vacation_hours - $worked_hours;
		$vacation_hours_left = max(0, -$work_left);
		// print_r([
		// 	'fte_ratio' => $fte_ratio,
		// 	'leftover_hours' => $leftover_hours,
		// 	'vacation_hours_per_second' => $vacation_hours_per_second,
		// 	'earned_hours' => $earned_hours,
		// 	'expected_work_hours' => $expected_work_hours,
		// 	'worked_hours' => $worked_hours,
		// 	'work_left' => $work_left,
		// 	'vacation_hours_left' => (int) ($vacation_hours_left),
		// ]);

		// Formatting as hours and minutes
		$hours = floor($vacation_hours_left);
		$minutes = ($vacation_hours_left - $hours) * 60;
		$formatted_vacation_hours = sprintf("%02d:%02d h", $hours, $minutes);

		return $formatted_vacation_hours;
	}
	public function getId(): string
	{
		return 'VacationWidget';
	}

	public function getTitle(): string
	{
		return 'Vacation hours left';
	}
	public function getOrder(): int
	{
		return 20;
	}

	public function getTemplateName(): string
	{
		return 'widget/widget-more.html.twig';
	}
}
