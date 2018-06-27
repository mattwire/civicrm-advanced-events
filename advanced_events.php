<?php

require_once 'advanced_events.civix.php';
use CRM_AdvancedEvents_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function advanced_events_civicrm_config(&$config) {
  _advanced_events_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function advanced_events_civicrm_xmlMenu(&$files) {
  _advanced_events_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function advanced_events_civicrm_install() {
  _advanced_events_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function advanced_events_civicrm_postInstall() {
  _advanced_events_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function advanced_events_civicrm_uninstall() {
  _advanced_events_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function advanced_events_civicrm_enable() {
  _advanced_events_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function advanced_events_civicrm_disable() {
  _advanced_events_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function advanced_events_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _advanced_events_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function advanced_events_civicrm_managed(&$entities) {
  _advanced_events_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function advanced_events_civicrm_caseTypes(&$caseTypes) {
  _advanced_events_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function advanced_events_civicrm_angularModules(&$angularModules) {
  _advanced_events_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function advanced_events_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _advanced_events_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function advanced_events_civicrm_navigationMenu(&$menu) {
  $item[] =  array (
    'label' => ts('Advanced Events Configuration'), array('domain' => E::LONG_NAME),
    'name'       => E::SHORT_NAME,
    'url'        => 'civicrm/admin/advanced_events/settings',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => 2,
  );
  _advanced_events_civix_insert_navigation_menu($menu, 'Administer/CiviEvent', $item[0]);
  _advanced_events_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function advanced_events_civicrm_entityTypes(&$entityTypes) {
  _advanced_events_civix_civicrm_entityTypes($entityTypes);
}

function advanced_events_civicrm_tabset($tabsetName, &$tabs, $context) {
  //check if the tab set is Event manage
  if ($tabsetName == 'civicrm/event/manage') {
    foreach (CRM_AdvancedEvents_Functions::getEnabled() as $functionName => $enabled) {
      if (empty($enabled)) {
        unset($tabs[$functionName]);
      }
    }
  }
}