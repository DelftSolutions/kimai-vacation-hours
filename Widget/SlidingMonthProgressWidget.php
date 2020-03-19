<?php

namespace KimaiPlugin\VacationHoursBundle\Widget;

use App\Repository\TimesheetRepository;
use DateTime;
use DateInterval;
use App\Security\CurrentUser;
use App\Widget\Type\SimpleWidget;

class SlidingMonthProgressWidget extends SimpleWidget
{
    protected $repository;

    public function __construct(TimesheetRepository $repository, CurrentUser $user)
    {
	$this->repository = $repository;

	$this->setId('SlidingMonthProgressWidget');
	$this->setTitle('Last 4 weeks hours left');
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
            $options['id'] = 'SlidingMonthProgressWidget';
        }

        return $options;
    }

    public function getData(array $options = [])
    {
	$options = $this->getOptions($options);
	$user = $options['user'];

	$week_length = 7 * 24 * 60 * 60;

	$accounting_start = strtotime('last monday') - 3 * $week_length;
	if ($accounting_start === false) return;
	$startDate = new DateTime();
	$startDate->setTimestamp($accounting_start);

	$accounting_end = strtotime('next monday');
	$seconds_elapsed = $accounting_end - $accounting_start;
	if ($seconds_elapsed < 0) return;
	$endDate = new DateTime();
	$endDate->setTimestamp($accounting_end);

	$fte_ratio = $user->getPreferenceValue('target-weekly-hours', 0) / 40.0;

	$elapsed_weeks = $seconds_elapsed / $week_length;

	$expected_work_hours = $elapsed_weeks * $fte_ratio * 40;

	$worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

	$work_left = max(0, $expected_work_hours - $worked_hours);

	return (int)($work_left * 60 * 60);
    }

    public function getTemplateName(): string
    {
	return 'widget/widget-more.html.twig';
    }
}
