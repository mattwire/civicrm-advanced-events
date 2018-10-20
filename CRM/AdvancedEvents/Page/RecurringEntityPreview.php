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
class CRM_AdvancedEvents_Page_RecurringEntityPreview extends CRM_Core_Page_RecurringEntityPreview {

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getTemplateFileName() {
    $entityTable = CRM_Utils_Request::retrieve('entity_table', 'String');
    if ($entityTable != 'civicrm_event') {
      return 'CRM/Core/Page/RecurringEntityPreview.tpl';
    }
    return parent::getTemplateFileName();
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   */
  public function run() {
    $startDate = $endDate = NULL;
    $dates = $original = array();
    $formValues = $_REQUEST;
    if (!empty($formValues['entity_table'])) {
      if ($formValues['entity_table'] != "civicrm_event") {
        // This triggers the CiviCRM core handling for everything other than events
        return parent::run();
      }
      $startDateColumnName = CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['dateColumns'][0];
      $endDateColumnName = CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['intervalDateColumns'][0];

      $recursion = new CRM_AdvancedEvents_BAO_RecurringEntity();
      if (CRM_Utils_Array::value('dateColumns', CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $recursion->dateColumns = CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['dateColumns'];
      }
      $recursion->scheduleFormValues = $formValues;
      if (!empty($formValues['exclude_date_list'])) {
        $recursion->excludeDates = explode(',', $formValues['exclude_date_list']);
      }
      if (CRM_Utils_Array::value('excludeDateRangeColumns', CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        $recursion->excludeDateRangeColumns = CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']]['excludeDateRangeColumns'];
      }

      $recursion->dontSkipStartDate = CRM_Utils_Array::value('dont_skip_start_date', $formValues);

      // Get original entity
      $original[$startDateColumnName] = CRM_Utils_Date::processDate($formValues['repetition_start_date']);
      $daoName = CRM_AdvancedEvents_BAO_RecurringEntity::$_tableDAOMapper[$formValues['entity_table']];
      if ($formValues['entity_id']) {
        $startDate = $original[$startDateColumnName] = CRM_Core_DAO::getFieldValue($daoName, $formValues['entity_id'], $startDateColumnName);
        $endDate = $original[$startDateColumnName] = $endDateColumnName ? CRM_Core_DAO::getFieldValue($daoName, $formValues['entity_id'], $endDateColumnName) : NULL;
      }

      //Check if there is any enddate column defined to find out the interval between the two range
      if (CRM_Utils_Array::value('intervalDateColumns', CRM_AdvancedEvents_BAO_RecurringEntity::$_dateColumns[$formValues['entity_table']])) {
        if ($endDate) {
          $interval = $recursion->getInterval($startDate, $endDate);
          $recursion->intervalDateColumns = array($endDateColumnName => $interval);
        }
      }

      $dates = $recursion->generateRecursiveDates();
      // Get original entity
      if (!$recursion->dontSkipStartDate) {
        $original[$startDateColumnName] = CRM_Utils_Array::value('repetition_start_date', $formValues);
        if (empty($original[$startDateColumnName])) {
          $original[$startDateColumnName] = date('YmdHis');
        }
        $dates = array_merge(array($original), $dates);
      }

      foreach ($dates as $key => &$value) {
        if ($startDateColumnName) {
          if (CRM_AdvancedEvents_BAO_EventTemplate::eventAlreadyExists($formValues['entity_id'], ['start_date' => $value[$startDateColumnName]])) {
            $value['exists'] = TRUE;
          }
        }
        if ($endDateColumnName && !empty($value[$endDateColumnName])) {
          $endDates = TRUE;
        }
      }

    }
    $this->assign('dates', $dates);
    $this->assign('endDates', !empty($endDates));

    return CRM_Core_Page::run();
  }

}
