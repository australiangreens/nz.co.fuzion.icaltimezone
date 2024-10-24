<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class to generate various "icalendar" type event feeds
 *
 * Overriden core civi file CRM_Event_ICalendar.
 */
class CRM_Icaltimezone_ICal extends CRM_Core_Page {

  // AG: PHP 8+ requires callback functions to be declared statically.
  // So we create a static wrapper method:
  public static function callback() {
    $instance = new self();
    return $instance->run();
  }

  /**
   * Heart of the iCalendar data assignment process. The runner gets all the meta
   * data for the event and calls the  method to output the iCalendar
   * to the user. If gData param is passed on the URL, outputs gData XML format.
   * Else outputs iCalendar format per IETF RFC2445. Page param true means send
   * to browser as inline content. Else, we send .ics file as attachment.
   */
  public function run() {
    $id = CRM_Utils_Request::retrieveValue('id', 'Positive', NULL, FALSE, 'GET');
    $type = CRM_Utils_Request::retrieveValue('type', 'Positive', 0);
    $start = CRM_Utils_Request::retrieveValue('start', 'Positive', 0);
    $end = CRM_Utils_Request::retrieveValue('end', 'Positive', 0);
    $gCalendar = CRM_Utils_Request::retrieveValue('gCalendar', 'Positive', 0);

    // We used to handle the event list as a html page at civicrm/event/ical - redirect to the new URL if that was what we requested.
    if (CRM_Utils_Request::retrieveValue('html', 'Positive', 0)) {
      $urlParams = [
        'reset' => 1,
      ];
      $id ? $urlParams['id'] = $id : NULL;
      $type ? $urlParams['type'] = $type : NULL;
      $start ? $urlParams['start'] = $start : NULL;
      $end ? $urlParams['end'] = $end : NULL;
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/list', $urlParams, FALSE, NULL, FALSE, TRUE));
    }
    if (!isset($_GET['timezone'])) {
      $urlParams = [
        'reset' => 1,
      ];
      $id ? $urlParams['id'] = $id : NULL;
      $type ? $urlParams['type'] = $type : NULL;
      $start ? $urlParams['start'] = $start : NULL;
      $end ? $urlParams['end'] = $end : NULL;
      $gCalendar ? $urlParams['gCalendar'] = $gCalendar : NULL;
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/ical/gettimezone', $urlParams, FALSE, NULL, FALSE, TRUE));
    }
    $userTimeZone = $_GET['timezone'] ?? CRM_Core_Config::singleton()->userSystem->getTimeZoneString();

    $iCalPage = CRM_Utils_Request::retrieveValue('list', 'Positive', 0);
    $gData = CRM_Utils_Request::retrieveValue('gData', 'Positive', 0);
    $rss = CRM_Utils_Request::retrieveValue('rss', 'Positive', 0);

    $template = CRM_Core_Smarty::singleton();
    $config = CRM_Core_Config::singleton();

    $info = CRM_Event_BAO_Event::getCompleteInfo($start, $type, $id, $end);
    // AG: use state/territory to set $defaultTimezone appropriately
    $sql = "
SELECT
  csp.abbreviation AS state
FROM civicrm_event ce
LEFT JOIN civicrm_loc_block clb ON clb.id = ce.loc_block_id
LEFT JOIN civicrm_address ca ON ca.id = clb.address_id
LEFT JOIN civicrm_state_province csp ON csp.id = ca.state_province_id
WHERE ce.id = %1";
    $params = [1 => [$id, 'Integer']];
    $state = CRM_Core_DAO::singleValueQuery($sql, $params);

    $eventTimezones = [
      'ACT' => 'Australia/Canberra',
      'NSW' => 'Australia/Sydney',
      'NT'  => 'Australia/Darwin',
      'QLD' => 'Australia/Brisbane',
      'SA'  => 'Australia/Adelaide',
      'TAS' => 'Australia/Hobart',
      'VIC' => 'Australia/Melbourne',
      'WA'  => 'Australia/Perth',
    ];
    $defaultTimezone = new DateTimeZone($eventTimezones[$state] ?? date_default_timezone_get());

    foreach ($info as &$eventInfo) {
      foreach (['start_date', 'end_date', 'registration_start_date', 'registration_end_date'] as $dateField) {
        if (!empty($eventInfo[$dateField])) {
          $dateObj = new DateTime($eventInfo[$dateField], $defaultTimezone);
          $eventInfo[$dateField] = self::convertDateToLocalTime($dateObj, $userTimeZone, 'Y-m-d H:i:s');
        }
      }
    }

    if ($gCalendar && count($info) === 1) {
      return self::gCalRedirect($info);
    }

    $template->assign('events', $info);
    $template->assign('timezone', $userTimeZone);

    // Send data to the correct template for formatting (iCal vs. gData)
    if ($rss) {
      // rss 2.0 requires lower case dash delimited locale
      $template->assign('rssLang', str_replace('_', '-', strtolower($config->lcMessages)));
      $calendar = $template->fetch('CRM/Core/Calendar/Rss.tpl');
    }
    elseif ($gData) {
      $calendar = $template->fetch('CRM/Core/Calendar/GData.tpl');
    }
    else {
      $calendar = $template->fetch('CRM/Core/Calendar/ICal.tpl');
      $calendar = preg_replace('/(?<!\r)\n/', "\r\n", $calendar);
    }

    // Push output for feed or download
    if ($iCalPage == 1) {
      if ($gData || $rss) {
        CRM_Utils_ICalendar::send($calendar, 'text/xml', 'utf-8');
      }
      else {
        CRM_Utils_ICalendar::send($calendar, 'text/calendar', 'utf-8');
      }
    }
    else {
      CRM_Utils_ICalendar::send($calendar, 'text/calendar', 'utf-8', 'civicrm_ical.ics', 'attachment');
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Print out a date object in specified format in local timezone
   *
   * @param DateTimeObject $dateObject
   * @param string $format
   * @return string
   */
  public static function convertDateToLocalTime($dateObject, $userTimeZone, $format = 'YmdHis') {
    $systemTimeZone = new DateTimeZone($userTimeZone);
    $dateObject->setTimezone($systemTimeZone);
    return $dateObject->format($format);
  }

  /*
   *
   * Copying this function from CRM/Event/ICalendar.php in civicrm-core
   * because it's a protected function and we cannot access it otherwise.
   * It's necessary to ensure the "Add to Google Calendar" functionality
   * works as designed on event info/registration pages.
   */
  protected static function gCalRedirect(array $events) {
    $event = reset($events);

    // Fetch the required Date TimeStamps
    $start_date = date_create($event['start_date']);

    // Google Requires that a Full Day event end day happens on the next Day
    $end_date = ($event['end_date']
      ? date_create($event['end_date'])
      : date_create($event['start_date'])
        ->add(DateInterval::createFromDateString('1 day'))
        ->setTime(0, 0, 0)
    );

    $dates = $start_date->format('Ymd\THis') . '/' . $end_date->format('Ymd\THis');

    $event_details = $event['description'];

    // Add space after paragraph
    $event_details = str_replace('</p>', '</p> ', $event_details);
    $event_details = strip_tags($event_details);

    // Truncate Event Description and add permalink if greater than 996 characters
    if (strlen($event_details) > 996) {
      if (preg_match('/^.{0,996}(?=\s|$_)/', $event_details, $m)) {
        $event_details = $m[0] . '...';
      }
    }

    $event_details .= "\n\n<a href=\"{$event['url']}\">" . ts('View %1 Details', [1 => $event['event_type']]) . '</a>';

    $params = [
      'action' => 'TEMPLATE',
      'text' => strip_tags($event['title']),
      'dates' => $dates,
      'details' => $event_details,
      'location' => str_replace("\n", "\t", $event['location']),
      'trp' => 'false',
      'sprop' => 'website:' . CRM_Utils_System::baseCMSURL(),
      'ctz' => @date_default_timezone_get(),
    ];

    $url = 'https://www.google.com/calendar/event?' . CRM_Utils_System::makeQueryString($params);

    CRM_Utils_System::redirect($url);
  }
}
