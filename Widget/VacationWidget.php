<?php

namespace KimaiPlugin\VacationHoursBundle\Widget;

use App\Widget\Type\AbstractWidget;
use App\Widget\Type\SimpleWidget;
use App\Widget\WidgetInterface;
use App\Repository\TimesheetRepository;
use KimaiPlugin\VacationHoursBundle\Library\VacationHoursCalculator;

final class VacationWidget extends AbstractWidget
{
	public function __construct(private TimesheetRepository $repository)
	{
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
		$user = $this->getUser();

		$vacation_hours_left = VacationHoursCalculator::calculatehours($user, $this->repository);

		if (is_null($vacation_hours_left))
			return null;

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
