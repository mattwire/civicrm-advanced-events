<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CRM_Event_Form_ManageEvent_Repeat extends CRM_Event_Form_ManageEvent {

  /**
   * Parent Event Start Date.
   */
  protected $_parentEventStartDate = NULL;

  /**
   * Parent Event End Date.
   */
  protected $_parentEventEndDate = NULL;

  /**
   * @return int
   */
  protected function getEventId() {
    return $this->_id;
  }

  public function preProcess() {
    parent::preProcess();

    if (empty($this->getEventId())) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->assign('templateId', $this->getEventId());
    $manageEvent = [];

    // Get details of all events linked to this template
    $eventsLinkedtoTemplate = civicrm_api3('EventTemplate', 'get', ['template_id' => $this->getEventId()]);
    $linkedEventIds = CRM_Utils_Array::collect('event_id', $eventsLinkedtoTemplate['values']);
    if (!empty($linkedEventIds)) {
      $eventParams = [
        'id' => ['IN' => $linkedEventIds],
        'options' => ['sort' => "start_date ASC", 'limit' => 0],
      ];
      $events = civicrm_api3('Event', 'get', $eventParams);
      foreach ($events['values'] as $eventId => $eventDetail) {
        if (CRM_AdvancedEvents_Temp::checkPermission($eventId, CRM_Core_Permission::VIEW)) {
          $manageEvent[$eventId] = $eventDetail;
          $manageEvent[$eventId]['participant_count'] = civicrm_api3('Participant', 'getcount', ['event_id' => $eventId]);
        }
      }
      $this->assign('rows', $manageEvent);
    }

    $parentEventParams = civicrm_api3('Event', 'get', ['id' => $this->getEventId(), 'return' => ['start_date', 'end_date']]);
    if ($parentEventParams['id']) {
      $parentEventParams = $parentEventParams['values'][$parentEventParams['id']];
      $this->_parentEventStartDate = CRM_Utils_Array::value('start_date', $parentEventParams);
      $this->_parentEventEndDate = CRM_Utils_Array::value('end_date', $parentEventParams);
    }
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();

    //Always pass current event's start date by default
    try {
      $currentEventStartDate = civicrm_api3('Event', 'getvalue', ['id' => $this->getEventId(), 'return' => 'start_date']);
    }
    catch (Exception $e) {
      $currentEventStartDate = NULL;
    }
    //list($defaults['repetition_start_date'], $defaults['repetition_start_date_time']) = CRM_Utils_Date::setDateDefaults($currentEventStartDate, 'activityDateTime');
    $defaults['repetition_start_date'] = $currentEventStartDate;
    $recurringEntityDefaults = CRM_AdvancedEvents_Form_RecurringEntity::setDefaultValues();
    return array_merge($defaults, $recurringEntityDefaults);
  }

  public function buildQuickForm() {
    CRM_AdvancedEvents_Form_RecurringEntity::buildQuickForm($this);
    $this->add('hidden', 'dont_skip_start_date');
  }

  public function postProcess() {
    if ($this->getEventId()) {
      $params = $this->controller->exportValues($this->_name);
      if ($this->_parentEventStartDate && $this->_parentEventEndDate) {
        $interval = CRM_AdvancedEvents_BAO_RecurringEntity::getInterval($this->_parentEventStartDate, $this->_parentEventEndDate);
        $params['intervalDateColumns'] = array('end_date' => $interval);
      }
      $params['dateColumns'] = array('start_date');
      $params['excludeDateRangeColumns'] = array('start_date', 'end_date');
      $params['entity_table'] = 'civicrm_event';
      $params['entity_id'] = $this->getEventId();

      // CRM-16568 - check if parent exist for the event.
      $params['parent_entity_id'] = $params['entity_id'];
      // Unset event id
      unset($params['id']);

      $url = 'civicrm/event/manage/repeat';
      $urlParams = "action=update&reset=1&id={$this->getEventId()}&selectedChild=repeat";

      CRM_AdvancedEvents_Form_RecurringEntity::postProcess($params);
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    else {
      CRM_Core_Error::fatal("Could not find Event ID");
    }
    parent::endPostProcess();
  }

}
