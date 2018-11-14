<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */

/**
 * This class provides the functionality to delete a group of events.
 * This class provides functionality for the actual deletion.
 */
class CRM_AdvancedEvents_Form_Task_CopyParticipants extends CRM_AdvancedEvents_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   * @throws \Exception
   */
  public function preProcess() {
    //check for edit participants
    if (!CRM_Core_Permission::checkActionPermission('CiviEvent', CRM_Core_Action::UPDATE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    $eventParams = [
      'id' => ['IN' => $this->_eventIds],
      'options' => ['limit' => 0, 'sort' => 'event_start_date ASC'],
      'return' => ['id', 'title', 'event_start_date'],
    ];
    $sourceEvents = civicrm_api3('Event', 'get', $eventParams);
    $eventList = [];
    $eventHasParticipants = FALSE;
    foreach ($sourceEvents['values'] as $event) {
      $participantCount = civicrm_api3('Participant', 'getcount', ['event_id' => $event['id']]);
      if (!$eventHasParticipants && ($participantCount > 0)) {
        // We store the earliest event ID that has participants so we can pre-select it.
        $eventHasParticipants = $event['id'];
      }
      $eventList[$event['id']] = "{$event['title']} (ID: {$event['id']}) (Participants: {$participantCount}) {$event['event_start_date']}";
    }
    if (!$eventHasParticipants) {
      CRM_Core_Error::statusBounce('You need to select an event that has some participants to copy from!');
    }
    else {
      $this->_defaultSourceEvent = $eventHasParticipants;
    }
    $this->add('select', 'event_source_id', ts('Source Event to copy participants from: '),
      $eventList,
      TRUE
    );
    $this->addDefaultButtons(ts('Copy Participants'), 'done');
  }

  /**
   * Pre-select the earliest event that has participants
   *
   * @return array|NULL
   */
  public function setDefaultValues() {
    $defaults['event_source_id'] = $this->_defaultSourceEvent;
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    $params = $this->exportValues();
    if (empty($params['event_source_id'])) {
      CRM_Core_Error::statusBounce('No source event found to copy participants from');
    }

    $sourceParticipants = civicrm_api3('Participant', 'get', [
      'event_id' => $params['event_source_id'],
    ]);
    if (empty($sourceParticipants['count'])) {
      CRM_Core_Error::statusBounce('The source event has no participants');
    }

    foreach ($this->_eventIds as $eventId) {
      if ($eventId == $params['event_source_id']) {
        continue;
      }
      // Get existing participants for each event for duplicate check
      $existingParticipants = civicrm_api3('Participant', 'get', [
        'return' => ["contact_id"],
        'event_id' => $eventId,
        'options' => ['limit' => 0],
      ]);
      $existingParticipantContactIds = CRM_Utils_Array::collect('contact_id', $existingParticipants['values']);
      foreach ($sourceParticipants['values'] as $participant) {
        // Check for contact already registered for event and don't add again
        if (in_array($participant['contact_id'], $existingParticipantContactIds)) {
          continue;
        }

        // Add the participant to the event
        $fieldsToUnset = ['id', 'participant_id', 'event_start_date', 'event_end_date', 'register_date', 'participant_register_date'];
        foreach ($fieldsToUnset as $field) {
          unset($participant[$field]);
        }
        $participant['event_id'] = $eventId;
        $participant['register_date'] = date('YmdHis');
        civicrm_api3('Participant', 'create', $participant);
      }
    }
    $eventCount = count($this->_eventIds) -1;
    $participantCount = $sourceParticipants['count'];

    $status = ts('%1 participants added to %2 events.', [1 => $participantCount, 2 => $eventCount]);

    CRM_Core_Session::setStatus($status, ts('Added'), 'info');
  }

}
