<?php

namespace KimaiPlugin\VacationHoursBundle\EventSubscriber;

use App\Event\DashboardEvent;
// use App\Widget\Type\CompoundRow;
use KimaiPlugin\VacationHoursBundle\Widget\DemoWidget;
use KimaiPlugin\VacationHoursBundle\Widget\WeekProgressWidget;
use KimaiPlugin\VacationHoursBundle\Widget\VacationWidget;
// use KimaiPlugin\VacationHoursBundle\Widget\SlidingMonthProgressWidget;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DashboardSubscriber implements EventSubscriberInterface
{
	// private $vacationWidget;
	// private $weekWidget;
	// private $slidingWidget;

	public function __construct(
		// VacationWidget $vacationWidget, WeekProgressWidget $weekWidget, SlidingMonthProgressWidget $slidingWidget
	)
	{
		// $this->vacationWidget = $vacationWidget;
		// $this->weekWidget = $weekWidget;
		// $this->slidingWidget = $slidingWidget;
	}

	public static function getSubscribedEvents(): array
	{
		return [
			DashboardEvent::class => ['onDashboardEvent', 100],
		];
	}

	public function onDashboardEvent(DashboardEvent $event)
	{
		// $section = new CompoundRow();
		// $section->setTitle('Vacation Hours');
		// $section->setOrder(20);

		$event->addWidget("VacationWidget");
		$event->addWidget("WeekProgressWidget");
		$event->addWidget("DemoWidget");
		// $event->addWidget("DemoWidget");
		// $event->addWidget($this->weekWidget);
		// $event->addWidget($this->slidingWidget);

		// $event->addSection($section);
	}
}
