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
    'callback',
  ];
}
