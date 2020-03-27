<?php

namespace KimaiPlugin\VacationHoursBundle\Widget;

use App\Repository\TimesheetRepository;
use DateTime;
use DateInterval;
use App\Security\CurrentUser;
use App\Widget\Type\SimpleWidget;

class VacationWidget extends SimpleWidget
{
    protected $repository;

    public function __construct(TimesheetRepository $repository, CurrentUser $user)
    {
	$this->repository = $repository;

	$this->setId('VacationWidget');
	$this->setTitle('Vacation hours left');
	$this->setOptions([
        	'user' => $user->getUser(),
        	'id' => '',
		'icon' => 'time',
		'dataType' => 'duration',
        ]);
    }

    public function getOptions(array $options = []): array
    {
        $options = parent::getOptions($options);

        if (empty($options['id'])) {
            $options['id'] = 'VacationWidget';
        }

        return $options;
    }

    public function getData(array $options = [])
    {
	$options = $this->getOptions($options);
	$user = $options['user'];

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

	$worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

	$work_left = $expected_work_hours - $total_vacation_hours - $worked_hours;
	$vacation_hours_left = max(0, -$work_left);

	return (int)($vacation_hours_left * 60 * 60);
    }

    public function getTemplateName(): string
    {
	return 'widget/widget-more.html.twig';
    }
}
