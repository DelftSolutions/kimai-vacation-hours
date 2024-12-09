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
                ->setEnabled($isAdmin)
                ->setType(IntegerType::class)
                ->setOptions(['label' => 'Target Weekly Hours' . ($isAdmin ? '' : ' (Read-Only)'), 'attr' => ['readonly' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('target-weekly-start', '1970-01-30'))
                ->setEnabled($isAdmin)
                ->setType(TextType::class)
                ->setOptions(['label'    => 'Target Weekly Start Date' . ($isAdmin ? '' : ' (Read-Only)'), 'attr' => ['readonly' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('yearly-vacation-hours', 168))
                ->setEnabled($isAdmin)
                ->setType(NumberType::class)
                ->setOptions(['label' => 'Yearly Vacation Hours' . ($isAdmin ? '' : ' (Read-Only)'), 'attr' => ['readonly' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('start-of-period-vacation-hours', 0))
                ->setEnabled($isAdmin)
                ->setType(NumberType::class)
                ->setOptions(['label' => 'Start of Period Vacation Hours' . ($isAdmin ? '' : ' (Read-Only)'), 'attr' => ['readonly' => !$isAdmin]]) // Make readonly if not admin
        );

        $event->addPreference(
            (new UserPreference('extra-vacation-hours', 0))
                ->setEnabled($isAdmin)
                ->setType(IntegerType::class)
                ->setOptions(['label' => 'Extra Vacation Hours' . ($isAdmin ? '' : ' (Read-Only)'), 'attr' => ['readonly' => !$isAdmin]]) // Make readonly if not admin
        );
    }

    public function loadUserProfile(PrepareUserEvent $event)
    {
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');

        if(!$isAdmin) {
          // if user is not admin, we show them an unsubmittable fake input, so they know their vacation information
          echo "<script>
          async function showVacationInformation() {
            if (window.DS_VacationData) {
              // The `loadUserProfile` is usually called multiple times,
              // and we need to bail out if this function is executed once.
              return;
            }

            window.DS_VacationData = {
                'Target Weekly Hours': " . $event->getUser()->getPreferenceValue('target-weekly-hours') . ",
                'Target Weekly Start': " . $event->getUser()->getPreferenceValue('target-weekly-start') . ",
                'Yearly Vacation Hours': " . $event->getUser()->getPreferenceValue('yearly-vacation-hours') . ",
                'Start of Period Vacation Hours': " . $event->getUser()->getPreferenceValue('start-of-period-vacation-hours') . ",
                'Extra Vacation Hours': " . $event->getUser()->getPreferenceValue('extra-vacation-hours') . ",
            };

            const form = document.querySelector('form');
            const formBody = form.querySelector('.card-body');

            Object.entries(window.DS_VacationData).forEach(([preferenceLabel, preferenceValue]) => {
              // we mimic how the form controls on Kimai preference page looks like
              const formControl = document.createElement('div');
              formControl.className = 'mb-3 row';
              formControl.innerHTML = `
                  <label class=\"col-form-label col-sm-2\">
                    \${preferenceLabel} (Read-Only)
                  </label>
                  <div class=\"col-sm-10\">
                    <input readonly=\"readonly\" disabled=\"disabled\" class=\"form-control\" value=\"\${preferenceValue}\">
                  </div>
              `;

              formBody.appendChild(formControl);
            })
          }

          document.addEventListener('DOMContentLoaded', showVacationInformation);
          </script>";
        }

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
        $vacation_hours_per_second = $user->getPreferenceValue('yearly-vacation-hours') / $year_length;

        $extra_vacation_hours = $user->getPreferenceValue('extra-vacation-hours');

        $earned_hours = $seconds_elapsed * $vacation_hours_per_second;
        $total_vacation_hours = $leftover_hours + $earned_hours + $extra_vacation_hours;

        $week_length = 7 * 24 * 60 * 60;
        $elapsed_weeks = $seconds_elapsed / $week_length;

        $expected_work_hours = $elapsed_weeks * $fte_ratio * 40;
        $worked_hours = $this->repository->getStatistic('duration', $startDate, $endDate, $user) / 60 / 60;

        $work_left = $expected_work_hours - $total_vacation_hours - $worked_hours;
        
        print_r($work_left);
    }
}
