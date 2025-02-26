<?php

namespace KimaiPlugin\VacationHoursBundle\EventSubscriber;

use App\Event\ThemeEvent;
use App\Repository\TimesheetRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ThemeEventSubscriber implements EventSubscriberInterface
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
            ThemeEvent::JAVASCRIPT => ['injectJavascript', 100],
        ];
    }

    public function injectJavascript(ThemeEvent $event): void
    {
        if (!$event->getUser())
            return;

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            // if user is not admin, we show them an unsubmittable fake input, so they know their vacation information
            $script = "<script>
              async function showVacationInformation() {
                if (window.DS_VacationData) {
                  // The `loadUserProfile` is usually called multiple times,
                  // and we need to bail out if this function is executed once.
                  return;
                }

                // we want to show the inputs to user only on prefs page
                if (!window.location.href.endsWith('/prefs')) return;

                window.DS_VacationData = {
                    'Target Weekly Hours': '" . $event->getUser()->getPreferenceValue('target-weekly-hours') . "',
                    'Target Weekly Start': '" . date_format(date_create($event->getUser()->getPreferenceValue('target-weekly-start')), "Y-m-d") . "',
                    'Yearly Vacation Days': '" . $event->getUser()->getPreferenceValue('yearly-vacation-days') . "',
                    'Start of Period Vacation Hours': '" . $event->getUser()->getPreferenceValue('start-of-period-vacation-hours') . "',
                    'Extra Vacation Days': '" . $event->getUser()->getPreferenceValue('extra-vacation-days') . "',
                };
		console.log(window.DS_VacationData);
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
                });
              }

              document.addEventListener('DOMContentLoaded', showVacationInformation);
	      </script>";
          $event->addContent($script);
        }
    }
}
