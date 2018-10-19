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
    'url'        => 'civicrm/admin/advancedevents/settings',
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
    if (empty($context['event_id'])) {
      // We are on the "Manage Events" page - disable repeat link
      unset($tabs['repeat']);
    }
    if (!empty($context['event_id'])) {
      $eventTemplate = civicrm_api3('Event', 'get', [
        'return' => ["id"],
        'is_template' => 1,
        'id' => $context['event_id'],
      ]);
      if ($eventTemplate['count'] == 1) {
        // We are on manage event detail and it's a template event - show repeat functions
        $tabs['repeat'] = [
          'title' => 'Repeat',
          'link' => NULL,
          'valid' => TRUE,
          'active' => TRUE,
          'current' => FALSE,
          'class' => 'livePage',
        ];
      }
      else {
        // We are on manage event detail and it's not a template event
        unset($tabs['repeat']);
      }
    }
  }
}

function advanced_events_civicrm_pre($op, $objectName, $id, &$params) {
  switch ($objectName) {
    case 'Event':
      switch ($op) {
        case 'create':
        case 'edit':
          // Templates do not get a title, but we need them to have one to use RecurringEntity to create events from them
          if (!empty($params['template_title']) && empty($params['title'])) {
            $params['title'] = $params['template_title'];
          }
          break;
      }
      break;
  }
}

function advanced_events_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  switch ($objectName) {
    case 'RecurringEntity':
      if ($op === 'create') {
        $entityTable = $objectRef->entity_table;
        if ($entityTable !== 'civicrm_event') {
          return;
        }
        $eventTemplateTitle = civicrm_api3('EventTemplate', 'getvalue', ['return' => 'title', 'event_id' => $objectRef->parent_id]);
        $params = [
          'event_id' => $objectRef->entity_id,
          'template_id' => $objectRef->parent_id,
          'title' => $eventTemplateTitle,
        ];

        civicrm_api3('EventTemplate', 'create', $params);
      }
      break;

    case 'Event':
      switch ($op) {
        case 'create':
          $templateId = CRM_Utils_Request::retrieveValue('template_id', 'Positive');
          $eventId = $objectId;
          if (empty($eventId) || empty($templateId)) {
            return;
          }
          $eventTemplateTitle = civicrm_api3('Event', 'getvalue', ['id' => $templateId, 'return' => 'template_title']);
          $params = [
            'event_id' => $objectId,
            'template_id' => $templateId,
            'title' => $eventTemplateTitle,
          ];
          civicrm_api3('EventTemplate', 'create', $params);
          break;
      }
      break;
  }
}

function advanced_events_civicrm_pageRun(&$page) {
  if ($page instanceof CRM_Event_Page_ManageEvent) {
    // Insert a link to the event template
    $rows = $page->get_template_vars()['rows'];
    foreach ($rows as $eventId => &$details) {
      if (is_numeric($eventId)) {
        $eventTemplate = civicrm_api3('EventTemplate', 'get', [
          'event_id' => $eventId,
          'return' => 'template_id, title'
        ]);
        if ($eventTemplate['count'] == 1) {
          $eventTemplateId = $eventTemplate['id'];
          $url = CRM_Utils_System::url('civicrm/event/manage/settings', "action=update&id={$eventTemplateId}&reset=1");
          $details['template'] = "<a class='action-item crm-hover-button' href='{$url}' target=_blank>{$eventTemplate['values'][$eventTemplate['id']]['title']}</a>";
        }
      }
    }
    $page->assign('rows', $rows);
  }
}

function advanced_events_civicrm_recurringEntity($op, $entityTable, &$fromCriteria, &$newParams, &$createRecurringEntity, &$abort) {
  if ($entityTable !== 'civicrm_event') {
    return;
  }

  if (CRM_AdvancedEvents_BAO_EventTemplate::eventAlreadyExists($fromCriteria['id'], ['start_date' => $newParams['start_date']])) {
    $abort = TRUE;
    return;
  }

  $newParams = array_merge($newParams, [
    'template_title' => '',
    'is_template' => FALSE,
    'parent_event_id' => NULL,
  ]);
}

/**
 * Implements hook_civicrm_entity_supported_info().
 * This allows EventTemplate entity to be used in Drupal Views etc.
 */
function advanced_events_civicrm_entity_supported_info(&$civicrm_entity_info) {
  $civicrm_entity_info['civicrm_event_template'] = array(
    'civicrm entity name' => 'event_template', // the api entity name
    'label property' => 'title', // name is the property we want to use for the entity label
    'permissions' => array(
      'view' => array('view event info'),
      'edit' => array('edit all events'),
      'update' => array('edit all events'),
      'create' => array('edit all events'),
      'delete' => array('delete in CiviEvent'),
    ),
    'display suite' => [
      'link fields' => [
        ['link_field' => 'event_id', 'target' => 'civicrm_event'],
        ['link_field' => 'template_id', 'target' => 'civicrm_event'],
      ]
    ]
  );
}

