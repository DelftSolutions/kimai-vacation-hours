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
		$options['icon'] = 'mail-sent';
		if (empty($options['id'])) {
			$options['id'] = 'VacationWidget';
		}

		return $options;
	}

	public function getData(array $options = []): mixed
	{
		$user = $this->getUser();

		$orig_vacation_hours_left = VacationHoursCalculator::calculatehours($user, $this->repository);
		return VacationHoursCalculator::formatHours($orig_vacation_hours_left);

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
