<?php
use CRM_AdvancedEvents_ExtensionUtil as E;

/**
 * EventTemplate.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_event_template_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * EventTemplate.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_event_template_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * EventTemplate.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_event_template_delete($params) {
  /*if (empty($params['id'])) {
    $eventTemplate = civicrm_api3('EventTemplate', 'getsingle', ['event_id' => $params['event_id']]);
    $params['id'] = $eventTemplate['id'];
  }*/
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

function _civicrm_api3_event_template_delete_spec(&$spec) {
  $spec['id']['api.required'] = 0;
}

/**
 * EventTemplate.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_event_template_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
