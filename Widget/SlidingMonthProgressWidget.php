<?php

namespace KimaiPlugin\VacationHoursBundle\Widget;

use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractWidget;
use DateTime;
use DateInterval;
// use App\Security\CurrentUser;
use App\Widget\Type\SimpleWidget;
use App\Widget\WidgetInterface;


final class SlidingMonthProgressWidget extends AbstractWidget
{
	public function __construct(private TimesheetRepository $repository)
	{
		// $this->repository = $repository;

		// $this->setId('SlidingMonthProgressWidget');
		// $this->setTitle('Last 4 weeks hours left');
		// $this->setOptions([
		//     	'user' => $user->getUser(),
		//     	'id' => '',
		// 	'icon' => 'time',
		// 	'dataType' => 'duration',
		//     ]);
	}

	public function getWidth(): int
	{
		return WidgetInterface::WIDTH_SMALL;
	}

	public function getHeight(): int
	{
		return WidgetInterface::HEIGHT_FULL/2;
	}

	public function getOptions(array $options = []): array
	{
		$options = parent::getOptions($options);

		$options['icon'] = 'success';
		if (empty($options['id'])) {
			$options['id'] = 'SlidingMonthProgressWidget';
		}

		return $options;
	}

	public function getData(array $options = []): mixed
	{
		$options = $this->getOptions($options);
		// $user = $options['user'];
		$week_length = 7 * 24 * 60 * 60;
		$accounting_start = strtotime('last monday') - 3 * $week_length;

		if ($accounting_start === false)
			return null;
		$startDate = new DateTime();
		$startDate->setTimestamp($accounting_start);

		$accounting_end = strtotime('next monday');
		$seconds_elapsed = $accounting_end - $accounting_start;
		if ($seconds_elapsed < 0)
			return null;
		$endDate = new DateTime();
		$user = $this->getUser();
		$endDate->setTimestamp($accounting_end);

		$fte_ratio = $user->getPreferenceValue('target-weekly-hours', 0) / 40.0;

		$elapsed_weeks = $seconds_elapsed / $week_length;

		$expected_work_hours = $elapsed_weeks * $fte_ratio * 40;

		$worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

		$work_left = max(0, $expected_work_hours - $worked_hours);


		$seconds_left = (int) ($work_left * 60 * 60);

		$hours = floor($seconds_left / 3600);
		$minutes = floor(($seconds_left % 3600) / 60);
		
		$formatted_time = sprintf("%02d:%02d h", $hours, $minutes);
		// print_r([
		// 	'fte_ratio' => $fte_ratio,
		// 	'elapsed_weeks' => $elapsed_weeks,
		// 	'expected_work_hours' => $expected_work_hours,
		// 	'worked_hours' => $worked_hours,
		// 	'work_left' => $work_left,
		// 	'work_left * 60 * 60' => (int) ($work_left * 60 * 60),
		// ]);

		return $formatted_time;
	}
	public function getId(): string
	{
		return 'SlidingMonthProgressWidget';
	}

	public function getTitle(): string
	{
		return '4 week hours left';
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
