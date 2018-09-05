<?php
use CRM_AdvancedEvents_ExtensionUtil as E;

class CRM_AdvancedEvents_BAO_EventTemplate extends CRM_AdvancedEvents_DAO_EventTemplate {

  /**
   * Create a new EventTemplate based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_AdvancedEvents_DAO_EventTemplate|NULL
   *
  public static function create($params) {
    $className = 'CRM_AdvancedEvents_DAO_EventTemplate';
    $entityName = 'EventTemplate';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

  public static function eventAlreadyExists($templateId, $params) {
    $eventParams = array_merge([
      'return' => ["id"],
      'is_template' => FALSE,
    ], $params);
    $existingEvents = civicrm_api3('Event', 'get', $eventParams);
    if ($existingEvents['count'] > 0) {
      foreach ($existingEvents['values'] as $eventId => $values) {
        try {
          $eventTemplateId = civicrm_api3('EventTemplate', 'getvalue', [
            'event_id' => $eventId,
            'return' => 'template_id'
          ]);
          if ($templateId == $eventTemplateId) {
            return TRUE;
          }
        }
        catch (CiviCRM_API3_Exception $e) {
          // We may have an event that is not linked to a template with the same params, but we want to ignore it.
        }
      }
    }
    return FALSE;
  }
}
