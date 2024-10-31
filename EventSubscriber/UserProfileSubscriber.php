<?php

namespace KimaiPlugin\VacationHoursBundle\EventSubscriber;

use App\Entity\UserPreference;
use App\Event\UserPreferenceEvent;
use App\Event\PrepareUserEvent;
use App\Repository\TimesheetRepository;
use Symfony\Component\Security\Core\Security;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserProfileSubscriber implements EventSubscriberInterface
{
    protected $repository;
    private $security;

    public function __construct(TimesheetRepository $repository, Security $security)
    {
        $this->repository = $repository;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserPreferenceEvent::class => ['loadUserPreferences', 200],
            PrepareUserEvent::class => ['loadUserProfile', 300]
        ];
    }

    public function loadUserPreferences(UserPreferenceEvent $event)
    {
        if (null === ($user = $event->getUser())) {
            return;
        }

        // Check if the user has the ROLE_ADMIN role
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        $event->addPreference(
            (new UserPreference('target-weekly-hours', 32))
                ->setValue(32)
                ->setType(IntegerType::class)
                ->setOptions(['attr' => ['disabled' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('target-weekly-start', '1970-01-30'))
                ->setValue('1970-01-30')
                ->setType(TextType::class)
                ->setOptions(['attr' => ['disabled' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('yearly-fte-vacation-hours', 168))
                ->setValue(35)
                ->setType(NumberType::class)
                ->setOptions(['attr' => ['disabled' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('start-of-period-vacation-hours', 0))
                ->setValue(0)
                ->setType(NumberType::class)
                ->setOptions(['attr' => ['disabled' => !$isAdmin]]) // Make readonly if not admin
        );
    }

    public function loadUserProfile(PrepareUserEvent $event)
    {
        if (null === ($user = $event->getUser())) {
            return;
        }

        $accounting_start = strtotime($user->getPreferenceValue('target-weekly-start'));
        if ($accounting_start === false) {
            return;
        }
        $startDate = new DateTime();
        $startDate->setTimestamp($accounting_start);

        $seconds_elapsed = time() - $accounting_start;
        if ($seconds_elapsed < 0) {
            return;
        }
        $endDate = new DateTime();

        $fte_ratio = $user->getPreferenceValue('target-weekly-hours', 0) / 40.0;
        $leftover_hours = $user->getPreferenceValue('start-of-period-vacation-hours', 0);

        // Not accounting for leap years
        $year_length = 365 * 24 * 60 * 60;
        $vacation_hours_per_second = 24 * $user->getPreferenceValue('yearly-fte-vacation-hours') / $year_length;

        $earned_hours = $fte_ratio * $seconds_elapsed * $vacation_hours_per_second;
        $total_vacation_hours = $leftover_hours + $earned_hours;

        $week_length = 7 * 24 * 60 * 60;
        $elapsed_weeks = $seconds_elapsed / $week_length;

        $expected_work_hours = $elapsed_weeks * $fte_ratio * 40;
        $worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

        $work_left = $expected_work_hours - $total_vacation_hours - $worked_hours;

        print_r($work_left);
    }
}
