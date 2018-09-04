<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Repeat
 *
 * @author Priyanka
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


  public function preProcess() {
    parent::preProcess();

    if (empty($this->_id)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->assign('templateId', $this->_id);
    $manageEvent = [];

    // Get details of all events linked to this template
    $eventsLinkedtoTemplate = civicrm_api3('EventTemplate', 'get', ['template_id' => $this->_id]);
    $linkedEventIds = CRM_Utils_Array::collect('event_id', $eventsLinkedtoTemplate['values']);
    if (!empty($linkedEventIds)) {
      $eventParams = [
        'id' => ['IN' => $linkedEventIds],
        'options' => ['limit' => 0],
      ];
      $events = civicrm_api3('Event', 'get', $eventParams);
      foreach ($events['values'] as $eventId => $eventDetail) {
        if (CRM_Event_BAO_Event::checkPermission($eventId, CRM_Core_Permission::VIEW)) {
          $manageEvent[$eventId] = $eventDetail;
          $manageEvent[$eventId]['participant_count'] = civicrm_api3('Participant', 'getcount', ['event_id' => $eventId]);
        }
      }
      $this->assign('rows', $manageEvent);
    }

    $parentEventParams = array('id' => $this->_id);
    $parentEventValues = array();
    $parentEventReturnProperties = array('start_date', 'end_date');
    $parentEventAttributes = CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $parentEventParams, $parentEventValues, $parentEventReturnProperties);
    $this->_parentEventStartDate = $parentEventAttributes->start_date;
    $this->_parentEventEndDate = $parentEventAttributes->end_date;
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();

    //Always pass current event's start date by default
    $currentEventStartDate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'start_date', 'id');
    //list($defaults['repetition_start_date'], $defaults['repetition_start_date_time']) = CRM_Utils_Date::setDateDefaults($currentEventStartDate, 'activityDateTime');
    $defaults['repetition_start_date'] = $currentEventStartDate;
    $defaults['dont_skip_start_date'] = TRUE;
    $recurringEntityDefaults = CRM_Core_Form_RecurringEntity::setDefaultValues();
    return array_merge($defaults, $recurringEntityDefaults);
  }

  public function buildQuickForm() {
    CRM_Core_Form_RecurringEntity::buildQuickForm($this);
    $this->add('hidden', 'dont_skip_start_date');
  }

  public function postProcess() {
    if ($this->_id) {
      $params = $this->controller->exportValues($this->_name);
      if ($this->_parentEventStartDate && $this->_parentEventEndDate) {
        $interval = CRM_Core_BAO_RecurringEntity::getInterval($this->_parentEventStartDate, $this->_parentEventEndDate);
        $params['intervalDateColumns'] = array('end_date' => $interval);
      }
      $params['dateColumns'] = array('start_date');
      $params['excludeDateRangeColumns'] = array('start_date', 'end_date');
      $params['entity_table'] = 'civicrm_event';
      $params['entity_id'] = $this->_id;

      // CRM-16568 - check if parent exist for the event.
      $params['parent_entity_id'] = $params['entity_id'];
      // Unset event id
      unset($params['id']);

      $url = 'civicrm/event/manage/repeat';
      $urlParams = "action=update&reset=1&id={$this->_id}";

      $linkedEntities = array(
        array(
          'table' => 'civicrm_price_set_entity',
          'findCriteria' => array(
            'entity_id' => $this->_id,
            'entity_table' => 'civicrm_event',
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => FALSE,
        ),
        array(
          'table' => 'civicrm_uf_join',
          'findCriteria' => array(
            'entity_id' => $this->_id,
            'entity_table' => 'civicrm_event',
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => FALSE,
        ),
        array(
          'table' => 'civicrm_tell_friend',
          'findCriteria' => array(
            'entity_id' => $this->_id,
            'entity_table' => 'civicrm_event',
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => TRUE,
        ),
        array(
          'table' => 'civicrm_pcp_block',
          'findCriteria' => array(
            'entity_id' => $this->_id,
            'entity_table' => 'civicrm_event',
          ),
          'linkedColumns' => array('entity_id'),
          'isRecurringEntityRecord' => TRUE,
        ),
      );
      $params['dont_skip_start_date'] = TRUE;
      $params['new_params']['civicrm_event'] = [
        'template_title' => '',
        'is_template' => 0,
        'parent_event_id' => NULL,
      ];
      CRM_Core_Form_RecurringEntity::postProcess($params, 'civicrm_event', $linkedEntities);
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    else {
      CRM_Core_Error::fatal("Could not find Event ID");
    }
    parent::endPostProcess();
  }

  /**
   * This function gets the number of participant count for the list of related event ids.
   *
   * @param array $listOfRelatedEntities
   *   List of related event ids .
   *
   *
   * @return array
   */
  static public function getParticipantCountforEvent($listOfRelatedEntities = array()) {
    $participantDetails = array();
    if (!empty($listOfRelatedEntities)) {
      $implodeRelatedEntities = implode(',', array_map(function ($entity) {
        return $entity['id'];
      }, $listOfRelatedEntities));
      if ($implodeRelatedEntities) {
        $query = "SELECT p.event_id as event_id,
          concat_ws(' ', e.title, concat_ws(' - ', DATE_FORMAT(e.start_date, '%b %d %Y %h:%i %p'), DATE_FORMAT(e.end_date, '%b %d %Y %h:%i %p'))) as event_data,
          count(p.id) as participant_count
          FROM civicrm_participant p, civicrm_event e
          WHERE p.event_id = e.id AND p.event_id IN ({$implodeRelatedEntities})
          GROUP BY p.event_id";
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $participantDetails['countByID'][$dao->event_id] = $dao->participant_count;
          $participantDetails['countByName'][$dao->event_id][$dao->event_data] = $dao->participant_count;
        }
      }
    }
    return $participantDetails;
  }

  /**
   * This function checks if there was any registration for related event ids,
   * and returns array of ids with no registrations
   *
   * @param string or int or object... $eventID
   *
   * @return array
   */
  public static function checkRegistrationForEvents($eventID) {
    CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted = [];
    return CRM_Core_BAO_RecurringEntity::$_entitiesToBeDeleted;
  }

}
