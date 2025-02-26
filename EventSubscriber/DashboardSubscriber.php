<?php

namespace KimaiPlugin\VacationHoursBundle\EventSubscriber;

use App\Event\DashboardEvent;
use KimaiPlugin\VacationHoursBundle\Widget\WeekProgressWidget;
use KimaiPlugin\VacationHoursBundle\Widget\VacationWidget;
use KimaiPlugin\VacationHoursBundle\Widget\SlidingMonthProgressWidget;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DashboardSubscriber implements EventSubscriberInterface
{

	public function __construct(){}

	public static function getSubscribedEvents(): array
	{
		return [
			DashboardEvent::class => ['onDashboardEvent', 100],
		];
	}

	public function onDashboardEvent(DashboardEvent $event)
	{

		$event->addWidget("VacationWidget");
		$event->addWidget("WeekProgressWidget");
		$event->addWidget("SlidingMonthProgressWidget");

	}
}
