<?php

require_once 'icaltimezone.civix.php';
use CRM_Icaltimezone_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function icaltimezone_civicrm_config(&$config) {
  _icaltimezone_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function icaltimezone_civicrm_install() {
  _icaltimezone_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function icaltimezone_civicrm_enable() {
  _icaltimezone_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_alterMenu().
 */
function icaltimezone_civicrm_alterMenu(&$items) {
  $items['civicrm/event/ical']['page_callback'] = [
    'CRM_Icaltimezone_ICal',
    'run',
  ];
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function icaltimezone_civicrm_navigationMenu(&$menu) {
  _icaltimezone_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _icaltimezone_civix_navigationMenu($menu);
} // */
