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
 */

require_once 'packages/When/When.php';

/**
 * Class CRM_AdvancedEvents_BAO_RecurringEntity.
 */
class CRM_AdvancedEvents_BAO_RecurringEntity extends CRM_Core_DAO_RecurringEntity {

  const RUNNING = 1;
  public $schedule = array();
  public $scheduleId = NULL;
  public $scheduleFormValues = array();

  public $dateColumns = array();
  public $intervalDateColumns = array();
  public $excludeDates = array();

  protected $recursion = NULL;
  protected $recursion_start_date = NULL;

  public static $_entitiesToBeDeleted = array();

  public static $status = NULL;

  static $_dateColumns
    = array(
      'civicrm_event' => array(
        'dateColumns' => array('start_date'),
        'excludeDateRangeColumns' => array('start_date', 'end_date'),
        'intervalDateColumns' => array('end_date'),
      ),
    );

  static $_tableDAOMapper
    = array(
      'civicrm_event' => 'CRM_Event_DAO_Event',
      'civicrm_price_set_entity' => 'CRM_Price_DAO_PriceSetEntity',
      'civicrm_uf_join' => 'CRM_Core_DAO_UFJoin',
      'civicrm_tell_friend' => 'CRM_Friend_DAO_Friend',
      'civicrm_pcp_block' => 'CRM_PCP_DAO_PCPBlock',
      'civicrm_activity' => 'CRM_Activity_DAO_Activity',
      'civicrm_activity_contact' => 'CRM_Activity_DAO_ActivityContact',
    );

  /**
   * Getter for status.
   *
   * @return string
   */
  public static function getStatus() {
    return self::$status;
  }

  /**
   * Setter for status.
   *
   * @param string $status
   */
  public static function setStatus($status) {
    self::$status = $status;
  }

  /**
   * This function generates all new entities based on object vars.
   *
   * @return array
   * @throws \Exception
   */
  public function generate() {
    $this->generateRecursiveDates();

    return $this->generateEntities();
  }

  /**
   * This function builds a "When" object based on schedule/reminder params
   *
   * @return object
   *   When object
   */
  public function generateRecursion() {
    // return if already generated
    if (is_a($this->recursion, 'When')) {
      return $this->recursion;
    }

    if ($this->scheduleId) {
      // get params by ID
      $this->schedule = $this->getScheduleParams($this->scheduleId);
    }
    elseif (!empty($this->scheduleFormValues)) {
      $this->schedule = $this->mapFormValuesToDB($this->scheduleFormValues);
    }

    if (!empty($this->schedule)) {
      $this->recursion = $this->getRecursionFromSchedule($this->schedule);
    }
    return $this->recursion;
  }

  /**
   * Generate new DAOs and along with entries in civicrm_recurring_entity table.
   *
   * @return array
   * @throws \Exception
   */
  public function generateEntities() {
    self::setStatus(self::RUNNING);

    $newEntities = array();
    if (!empty($this->recursionDates)) {
      if (empty($this->entity_id)) {
        CRM_Core_Error::fatal("Find criteria missing to generate form. Make sure entity_id and table is set.");
      }
      $count = 0;
      foreach ($this->recursionDates as $key => $dateCols) {
        $newCriteria = $dateCols;
        // create main entities
        CRM_AdvancedEvents_BAO_RecurringEntity::copyCreateEntity($this->entity_id, $newCriteria);
        $count++;
      }
    }

    self::$status = NULL;
    return $newEntities;
  }

  /**
   * This function iterates through when object criteria and
   * generates recursive dates based on that
   *
   * @return array
   *   array of dates
   */
  public function generateRecursiveDates() {
    $this->generateRecursion();

    $recursionDates = [];
    if (is_a($this->recursion, 'When')) {
      $initialCount = CRM_Utils_Array::value('start_action_offset', $this->schedule) + 1;

      $exRangeStart = $exRangeEnd = NULL;
      if (!empty($this->excludeDateRangeColumns)) {
        $exRangeStart = $this->excludeDateRangeColumns[0];
        $exRangeEnd = $this->excludeDateRangeColumns[1];
      }

      $count = 0;
      $this->recursion->count($initialCount);
      while ($result = $this->recursion->next()) {
        $skip = FALSE;
        $baseDate = $result->format('YmdHis');

        foreach ($this->dateColumns as $col) {
          $recursionDates[$count][$col] = $baseDate;
        }
        foreach ($this->intervalDateColumns as $col => $interval) {
          $newDate = new DateTime($baseDate);
          $newDate->add($interval);
          $recursionDates[$count][$col] = $newDate->format('YmdHis');
        }
        if ($exRangeStart) {
          $exRangeStartDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value($exRangeStart, $recursionDates[$count]), NULL, FALSE, 'Ymd');
          $exRangeEndDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value($exRangeEnd, $recursionDates[$count]), NULL, FALSE, 'Ymd');
        }

        foreach ($this->excludeDates as $exDate) {
          $exDate = CRM_Utils_Date::processDate($exDate, NULL, FALSE, 'Ymd');
          if (!$exRangeStart) {
            if ($exDate == $result->format('Ymd')) {
              $skip = TRUE;
              break;
            }
          }
          else {
            if (($exDate == $exRangeStartDate) ||
              ($exRangeEndDate && ($exDate > $exRangeStartDate) && ($exDate <= $exRangeEndDate))
            ) {
              $skip = TRUE;
              break;
            }
          }
        }

        if ($skip) {
          unset($recursionDates[$count]);
          // lets increase the counter, so we get correct number of occurrences
          $initialCount++;
          $this->recursion->count($initialCount);
          continue;
        }

        if (isset($this->schedule['absolute_date']) && !empty($result)) {
          if ($result < new DateTime($this->schedule['absolute_date'])) {
            $initialCount++;
            $this->recursion->count($initialCount);
          }
        }
        $count++;
      }
    }
    $this->recursionDates = $recursionDates;

    return $recursionDates;
  }

  /**
   * This function copies the information from parent entity and creates other entities with same information.
   *
   * @param $templateId
   * @param array $newParams
   *   Array of all the fields & values to be copied besides the other fields.
   *
   * @return array Event.create API Result
   * @throws \CiviCRM_API3_Exception
   */
  public static function copyCreateEntity($templateId, $newParams) {
    // We need the titles from the template
    $eventTemplate = civicrm_api3('Event', 'getsingle', ['id' => $templateId, 'return' => ['template_title', 'title']]);
    $params = [
      'is_template' => 0,
      'template_title' => '',
      'parent_event_id' => NULL,
      'start_date' => $newParams['start_date'],
      'template_id' => $templateId,
      'title' => $eventTemplate['title'],
    ];
    // Now create the event
    $newEvent = civicrm_api3('Event', 'create', $params);

    $templateParams = [
      'event_id' => $newEvent['id'],
      'template_id' => $templateId,
      'title' => $eventTemplate['template_title'],
    ];
    // Now create the entry in Event Template
    civicrm_api3('EventTemplate', 'create', $templateParams);

    return $newEvent;
  }

  /**
   * This function maps values posted from form to civicrm_action_schedule columns.
   *
   * @param array $formParams
   *   And array of form values posted .
   *
   * @return array
   */
  public function mapFormValuesToDB($formParams = array()) {
    $dbParams = array();
    if (!empty($formParams['used_for'])) {
      $dbParams['used_for'] = $formParams['used_for'];
    }

    if (!empty($formParams['entity_id'])) {
      $dbParams['entity_value'] = $formParams['entity_id'];
    }

    if (!empty($formParams['repetition_start_date'])) {
      $repetitionStartDate = $formParams['repetition_start_date'];
      $repetition_start_date = new DateTime($repetitionStartDate);
      $dbParams['start_action_date'] = $repetition_start_date->format('YmdHis');
    }

    if (!empty($formParams['repetition_frequency_unit'])) {
      $dbParams['repetition_frequency_unit'] = $formParams['repetition_frequency_unit'];
    }

    if (!empty($formParams['repetition_frequency_interval'])) {
      $dbParams['repetition_frequency_interval'] = $formParams['repetition_frequency_interval'];
    }

    //For Repeats on:(weekly case)
    if ($formParams['repetition_frequency_unit'] == 'week') {
      if (!empty($formParams['start_action_condition'])) {
        $repeats_on = CRM_Utils_Array::value('start_action_condition', $formParams);
        $dbParams['start_action_condition'] = implode(",", array_keys($repeats_on));
      }
    }

    //For Repeats By:(monthly case)
    if ($formParams['repetition_frequency_unit'] == 'month') {
      if ($formParams['repeats_by'] == 1) {
        if (!empty($formParams['limit_to'])) {
          $dbParams['limit_to'] = $formParams['limit_to'];
        }
      }
      if ($formParams['repeats_by'] == 2) {
        if (CRM_Utils_Array::value('entity_status_1', $formParams) && CRM_Utils_Array::value('entity_status_2', $formParams)) {
          $dbParams['entity_status'] = $formParams['entity_status_1'] . " " . $formParams['entity_status_2'];
        }
      }
    }

    //For "Ends" - After:
    if ($formParams['ends'] == 1) {
      if (!empty($formParams['start_action_offset'])) {
        $dbParams['start_action_offset'] = $formParams['start_action_offset'];
      }
    }

    //For "Ends" - On:
    if ($formParams['ends'] == 2) {
      if (!empty($formParams['repeat_absolute_date'])) {
        $dbParams['absolute_date'] = $formParams['repeat_absolute_date'];
      }
    }
    return $dbParams;
  }

  /**
   * This function gets all the columns of civicrm_action_schedule table based on id(primary key)
   *
   * @param int $scheduleReminderId
   *   Primary key of civicrm_action_schedule table.
   *
   * @return object
   */
  static public function getScheduleReminderDetailsById($scheduleReminderId) {
    $query = "SELECT *
      FROM civicrm_action_schedule WHERE 1";
    if ($scheduleReminderId) {
      $query .= "
        AND id = %1";
    }
    $dao = CRM_Core_DAO::executeQuery($query,
      array(
        1 => array($scheduleReminderId, 'Integer'),
      )
    );
    $dao->fetch();
    return $dao;
  }

  /**
   * wrapper of getScheduleReminderDetailsById function.
   *
   * @param int $scheduleReminderId
   *   Primary key of civicrm_action_schedule table.
   *
   * @return array
   */
  public function getScheduleParams($scheduleReminderId) {
    $scheduleReminderDetails = array();
    if ($scheduleReminderId) {
      //Get all the details from schedule reminder table
      $scheduleReminderDetails = self::getScheduleReminderDetailsById($scheduleReminderId);
      $scheduleReminderDetails = (array) $scheduleReminderDetails;
    }
    return $scheduleReminderDetails;
  }

  /**
   * This function takes criteria saved in civicrm_action_schedule table
   * and creates recursion rule
   *
   * @param array $scheduleReminderDetails
   *   Array of repeat criteria saved in civicrm_action_schedule table .
   *
   * @return object
   *   When object
   */
  public function getRecursionFromSchedule($scheduleReminderDetails = array()) {
    $r = new When();
    //If there is some data for this id
    if ($scheduleReminderDetails['repetition_frequency_unit']) {
      if ($scheduleReminderDetails['start_action_date']) {
        $currDate = date('Y-m-d H:i:s', strtotime($scheduleReminderDetails['start_action_date']));
      }
      else {
        $currDate = date("Y-m-d H:i:s");
      }
      $start = new DateTime($currDate);
      $this->recursion_start_date = $start;
      if ($scheduleReminderDetails['repetition_frequency_unit']) {
        $repetition_frequency_unit = $scheduleReminderDetails['repetition_frequency_unit'];
        if ($repetition_frequency_unit == "day") {
          $repetition_frequency_unit = "dai";
        }
        $repetition_frequency_unit = $repetition_frequency_unit . 'ly';
        $r->recur($start, $repetition_frequency_unit);
      }

      if ($scheduleReminderDetails['repetition_frequency_interval']) {
        $r->interval($scheduleReminderDetails['repetition_frequency_interval']);
      }
      else {
        $r->errors[] = 'Repeats every: is a required field';
      }

      //week
      if ($scheduleReminderDetails['repetition_frequency_unit'] == 'week') {
        if ($scheduleReminderDetails['start_action_condition']) {
          $startActionCondition = $scheduleReminderDetails['start_action_condition'];
          $explodeStartActionCondition = explode(',', $startActionCondition);
          $buildRuleArray = array();
          foreach ($explodeStartActionCondition as $key => $val) {
            $buildRuleArray[] = strtoupper(substr($val, 0, 2));
          }
          $r->wkst('MO')->byday($buildRuleArray);
        }
      }

      //month
      if ($scheduleReminderDetails['repetition_frequency_unit'] == 'month') {
        if ($scheduleReminderDetails['entity_status']) {
          $startActionDate = explode(" ", $scheduleReminderDetails['entity_status']);
          switch ($startActionDate[0]) {
            case 'first':
              $startActionDate1 = 1;
              break;

            case 'second':
              $startActionDate1 = 2;
              break;

            case 'third':
              $startActionDate1 = 3;
              break;

            case 'fourth':
              $startActionDate1 = 4;
              break;

            case 'last':
              $startActionDate1 = -1;
              break;
          }
          $concatStartActionDateBits = $startActionDate1 . strtoupper(substr($startActionDate[1], 0, 2));
          $r->byday(array($concatStartActionDateBits));
        }
        elseif ($scheduleReminderDetails['limit_to']) {
          $r->bymonthday(array($scheduleReminderDetails['limit_to']));
        }
      }

      //Ends
      if ($scheduleReminderDetails['start_action_offset']) {
        if ($scheduleReminderDetails['start_action_offset'] > 30) {
          $r->errors[] = 'Occurrences should be less than or equal to 30';
        }
        $r->count($scheduleReminderDetails['start_action_offset']);
      }

      if (!empty($scheduleReminderDetails['absolute_date'])) {
        // absolute_date column of scheduled-reminder table is of type date (and not datetime)
        // and we always want the date to be included, and therefore appending 23:59
        $endDate = new DateTime($scheduleReminderDetails['absolute_date'] . ' ' . '23:59:59');
        $r->until($endDate);
      }

      if (!$scheduleReminderDetails['start_action_offset'] && !$scheduleReminderDetails['absolute_date']) {
        $r->errors[] = 'Ends: is a required field';
      }
    }
    else {
      $r->errors[] = 'Repeats: is a required field';
    }
    return $r;
  }

  /**
   * This function gets time difference between the two datetime object.
   *
   * @param DateTime $startDate
   *   Start Date.
   * @param DateTime $endDate
   *   End Date.
   *
   * @return object
   *   DateTime object which contain time difference
   */
  public static function getInterval($startDate, $endDate) {
    if ($startDate && $endDate) {
      $startDate = new DateTime($startDate);
      $endDate = new DateTime($endDate);
      return $startDate->diff($endDate);
    }
  }

  /**
   * This function gets all columns from civicrm_action_schedule on the basis of event id.
   *
   * @param int $entityId
   *   Entity ID.
   * @param string $used_for
   *   Specifies for which entity type it's used for.
   *
   * @return object
   */
  public static function getReminderDetailsByEntityId($entityId, $used_for) {
    if ($entityId) {
      $query = "
        SELECT *
        FROM   civicrm_action_schedule
        WHERE  entity_value = %1";
      if ($used_for) {
        $query .= " AND used_for = %2";
      }
      $params = array(
        1 => array($entityId, 'Integer'),
        2 => array($used_for, 'String'),
      );
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      $dao->fetch();
    }
    return $dao;
  }

}
