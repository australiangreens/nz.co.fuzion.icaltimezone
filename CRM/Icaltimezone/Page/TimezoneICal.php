<?php
use CRM_Icaltimezone_ExtensionUtil as E;

class CRM_Icaltimezone_Page_TimezoneICal extends CRM_Core_Page {

  public function run() {
    $gCalendar = CRM_Utils_Request::retrieveValue('gCalendar', 'Positive', 0);
    if ($gCalendar) {
      $this->assign('gCalendar', $gCalendar);
    }
    Civi::resources()->addScriptFile('nz.co.fuzion.icaltimezone', 'js/jstz.js');
    Civi::resources()->addScriptFile('nz.co.fuzion.icaltimezone', 'js/timezone.js');
    parent::run();
  }

}
