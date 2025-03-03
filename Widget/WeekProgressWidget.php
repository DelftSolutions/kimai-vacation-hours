<?php

namespace KimaiPlugin\VacationHoursBundle\Widget;

use App\Repository\TimesheetRepository;
use App\Widget\Type\AbstractWidget;
use DateTime;
use DateInterval;
use App\Entity\User;
use App\Widget\Type\SimpleWidget;
use App\Widget\WidgetInterface;

final class WeekProgressWidget extends AbstractWidget
{

	public function __construct(private TimesheetRepository $repository)
	{}

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

		$options['icon'] = 'fa-regular fa-business-time';
		if (empty($options['id'])) {
			$options['id'] = 'WeekProgressWidget';

		}

		return $options;
	}

	public function getData(array $options = []): mixed
	{

		$options = $this->getOptions($options);

		$accounting_start = strtotime('monday this week 00:00:00');
		if ($accounting_start === false)
			return null;
		$startDate = new DateTime();
		$startDate->setTimestamp($accounting_start);

		$accounting_end = strtotime('next monday');
		$seconds_elapsed = $accounting_end - $accounting_start;
		if ($seconds_elapsed < 0)
			return null;
		$endDate = new DateTime();
		$endDate->setTimestamp($accounting_end);

		$user = $this->getUser();
		$fte_ratio = $user->getPreferenceValue('target-weekly-hours', 0) / 40.0;

		$week_length = 7 * 24 * 60 * 60;
		$elapsed_weeks = $seconds_elapsed / $week_length;

		$expected_work_hours = $elapsed_weeks * $fte_ratio * 40;

		$worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

		$work_left = max(0, $expected_work_hours - $worked_hours);


		$seconds_left = (int) ($work_left * 60 * 60);

		$hours = floor($seconds_left / 3600);
		$minutes = floor(($seconds_left % 3600) / 60);

		$formatted_time = sprintf("%02d:%02d h", $hours, $minutes);

		return $formatted_time;
	}
	public function getId(): string
	{
		return 'WeekProgressWidget';
	}

	public function getTitle(): string
	{
		return 'Week hours left';
	}

	public function getTemplateName(): string
	{
		return 'widget/widget-more.html.twig';
	}
}
