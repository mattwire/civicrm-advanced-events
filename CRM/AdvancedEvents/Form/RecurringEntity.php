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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for processing Entity.
 */
class CRM_AdvancedEvents_Form_RecurringEntity {
  /**
   *  Current entity id
   */
  protected static $_entityId = NULL;

  /**
   * Schedule Reminder ID
   */
  protected static $_scheduleReminderID = NULL;

  /**
   * Schedule Reminder data
   */
  protected static $_scheduleReminderDetails = array();

  /**
   *  Parent Entity ID
   */
  protected static $_parentEntityId = NULL;

  /**
   * Exclude date information
   */
  public static $_excludeDateInfo = array();

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Repeat Event');
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   */
  public static function setDefaultValues() {
    // Defaults for new entity
    $defaults = array(
      'repetition_frequency_unit' => 'week',
    );

    // Default for existing entity
    if (self::$_scheduleReminderID) {
      $defaults['repetition_frequency_unit'] = self::$_scheduleReminderDetails->repetition_frequency_unit;
      $defaults['repetition_frequency_interval'] = self::$_scheduleReminderDetails->repetition_frequency_interval;
      $defaults['start_action_condition'] = array_flip(explode(",", self::$_scheduleReminderDetails->start_action_condition));
      foreach ($defaults['start_action_condition'] as $key => $val) {
        $val = 1;
        $defaults['start_action_condition'][$key] = $val;
      }
      $defaults['start_action_offset'] = self::$_scheduleReminderDetails->start_action_offset;
      if (self::$_scheduleReminderDetails->start_action_offset) {
        $defaults['ends'] = 1;
      }
      $defaults['repeat_absolute_date'] = self::$_scheduleReminderDetails->absolute_date;
      if (self::$_scheduleReminderDetails->absolute_date) {
        $defaults['ends'] = 2;
      }
      $defaults['limit_to'] = self::$_scheduleReminderDetails->limit_to;
      if (self::$_scheduleReminderDetails->limit_to) {
        $defaults['repeats_by'] = 1;
      }
      if (self::$_scheduleReminderDetails->entity_status) {
        $explodeStartActionCondition = explode(" ", self::$_scheduleReminderDetails->entity_status);
        $defaults['entity_status_1'] = $explodeStartActionCondition[0];
        $defaults['entity_status_2'] = $explodeStartActionCondition[1];
      }
      if (self::$_scheduleReminderDetails->entity_status) {
        $defaults['repeats_by'] = 2;
      }
      if (self::$_excludeDateInfo) {
        $defaults['exclude_date_list'] = implode(',', self::$_excludeDateInfo);
      }
    }
    return $defaults;
  }

  /**
   * Build form.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    // FIXME: this is using the following as keys rather than the standard numeric keys returned by CRM_Utils_Date
    $dayOfTheWeek = array();
    $dayKeys = array(
      'sunday',
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
    );
    foreach (CRM_Utils_Date::getAbbrWeekdayNames() as $k => $label) {
      $dayOfTheWeek[$dayKeys[$k]] = $label;
    }
    $form->add('select', 'repetition_frequency_unit', ts('Repeats every'), CRM_Core_SelectValues::getRecurringFrequencyUnits(), FALSE, array('class' => 'required'));
    $numericOptions = CRM_Core_SelectValues::getNumericOptions(1, 30);
    $form->add('select', 'repetition_frequency_interval', NULL, $numericOptions, FALSE, array('class' => 'required'));
    $form->add('datepicker', 'repetition_start_date', ts('Start Date'), array(), FALSE, array('time' => TRUE));
    foreach ($dayOfTheWeek as $key => $val) {
      $startActionCondition[] = $form->createElement('checkbox', $key, NULL, $val);
    }
    $form->addGroup($startActionCondition, 'start_action_condition', ts('Repeats on'));
    $roptionTypes = array(
      '1' => ts('day of the month'),
      '2' => ts('day of the week'),
    );
    $form->addRadio('repeats_by', ts("Repeats on"), $roptionTypes, array('required' => TRUE), NULL);
    $form->add('select', 'limit_to', '', CRM_Core_SelectValues::getNumericOptions(1, 31));
    $dayOfTheWeekNo = array(
      'first' => ts('First'),
      'second' => ts('Second'),
      'third' => ts('Third'),
      'fourth' => ts('Fourth'),
      'last' => ts('Last'),
    );
    $form->add('select', 'entity_status_1', '', $dayOfTheWeekNo);
    $form->add('select', 'entity_status_2', '', $dayOfTheWeek);
    $eoptionTypes = array(
      '1' => ts('After'),
      '2' => ts('On'),
    );
    $form->addRadio('ends', ts("Ends"), $eoptionTypes, array('class' => 'required'), NULL);
    // Offset options gets key=>val pairs like 1=>2 because the BAO wants to know the number of
    // children while it makes more sense to the user to see the total number including the parent.
    $offsetOptions = range(1, 30);
    unset($offsetOptions[0]);
    $form->add('select', 'start_action_offset', NULL, $offsetOptions, FALSE);
    $form->addFormRule(array('CRM_Core_Form_RecurringEntity', 'formRule'));
    $form->add('datepicker', 'repeat_absolute_date', ts('On'), array(), FALSE, array('time' => FALSE));
    $form->add('text', 'exclude_date_list', ts('Exclude Dates'), array('class' => 'twenty'));
    $form->addElement('hidden', 'allowRepeatConfigToSubmit', '', array('id' => 'allowRepeatConfigToSubmit'));
    $form->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    // For client-side pluralization
    $form->assign('recurringFrequencyOptions', array(
      'single' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::getRecurringFrequencyUnits()),
      'plural' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::getRecurringFrequencyUnits(2)),
    ));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = array();
    //Process this function only when you get this variable
    if ($values['allowRepeatConfigToSubmit'] == 1) {
      $dayOfTheWeek = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
      //Repeats
      if (empty($values['repetition_frequency_unit'])) {
        $errors['repetition_frequency_unit'] = ts('This is a required field');
      }
      //Repeats every
      if (empty($values['repetition_frequency_interval'])) {
        $errors['repetition_frequency_interval'] = ts('This is a required field');
      }
      //Ends
      if (!empty($values['ends'])) {
        if ($values['ends'] == 1) {
          if (empty($values['start_action_offset'])) {
            $errors['start_action_offset'] = ts('This is a required field');
          }
          elseif ($values['start_action_offset'] > 30) {
            $errors['start_action_offset'] = ts('Occurrences should be less than or equal to 30');
          }
        }
        if ($values['ends'] == 2) {
          if (!empty($values['repeat_absolute_date'])) {
            $entityStartDate = CRM_Utils_Date::processDate($values['repetition_start_date']);
            $end = CRM_Utils_Date::processDate($values['repeat_absolute_date']);
            if (($end < $entityStartDate) && ($end != 0)) {
              $errors['repeat_absolute_date'] = ts('End date should be after current entity\'s start date');
            }
          }
          else {
            $errors['repeat_absolute_date'] = ts('This is a required field');
          }
        }
      }
      else {
        $errors['ends'] = ts('This is a required field');
      }

      //Repeats BY
      if (!empty($values['repeats_by'])) {
        if ($values['repeats_by'] == 1) {
          if (!empty($values['limit_to'])) {
            if ($values['limit_to'] < 1 && $values['limit_to'] > 31) {
              $errors['limit_to'] = ts('Invalid day of the month');
            }
          }
          else {
            $errors['limit_to'] = ts('Invalid day of the month');
          }
        }
        if ($values['repeats_by'] == 2) {
          if (!empty($values['entity_status_1'])) {
            $dayOfTheWeekNo = array('first', 'second', 'third', 'fourth', 'last');
            if (!in_array($values['entity_status_1'], $dayOfTheWeekNo)) {
              $errors['entity_status_1'] = ts('Invalid option');
            }
          }
          else {
            $errors['entity_status_1'] = ts('Invalid option');
          }
          if (!empty($values['entity_status_2'])) {
            if (!in_array($values['entity_status_2'], $dayOfTheWeek)) {
              $errors['entity_status_2'] = ts('Invalid day name');
            }
          }
          else {
            $errors['entity_status_2'] = ts('Invalid day name');
          }
        }
      }
    }
    return $errors;
  }

  /**
   * Process the form submission.
   *
   * @param array $params
   *
   * @throws \Exception
   */
  public static function postProcess($params = []) {
    $type = 'civicrm_event';
    // Check entity_id not present in params take it from class variable
    if (empty($params['entity_id'])) {
      $params['entity_id'] = self::$_entityId;
    }
    //Process this function only when you get this variable
    if ($params['allowRepeatConfigToSubmit'] == 1) {
      if (!empty($params['entity_table']) && !empty($params['entity_id']) && $type) {
        $params['used_for'] = $type;
        if (empty($params['parent_entity_id'])) {
          $params['parent_entity_id'] = self::$_parentEntityId;
        }
        if (!empty($params['schedule_reminder_id'])) {
          $params['id'] = $params['schedule_reminder_id'];
        }
        else {
          $params['id'] = self::$_scheduleReminderID;
        }

        //Save post params to the schedule reminder table
        $recurobj = new CRM_AdvancedEvents_BAO_RecurringEntity();
        $dbParams = $recurobj->mapFormValuesToDB($params);

        //Delete repeat configuration and rebuild
        if (!empty($params['id'])) {
          CRM_Core_BAO_ActionSchedule::del($params['id']);
          unset($params['id']);
        }
        $actionScheduleObj = CRM_Core_BAO_ActionSchedule::add($dbParams);

        //exclude dates
        $excludeDateList = array();
        if (CRM_Utils_Array::value('exclude_date_list', $params) && CRM_Utils_Array::value('parent_entity_id', $params) && $actionScheduleObj->entity_value) {
          //Since we get comma separated values lets get them in array
          $excludeDates = explode(",", $params['exclude_date_list']);

          //Check if there exists any values for this option group
          $optionGroupIdExists = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
            $type . '_repeat_exclude_dates_' . $params['parent_entity_id'],
            'id',
            'name'
          );
          if ($optionGroupIdExists) {
            CRM_Core_BAO_OptionGroup::del($optionGroupIdExists);
          }
          $optionGroupParams = array(
            'name' => $type . '_repeat_exclude_dates_' . $actionScheduleObj->entity_value,
            'title' => $type . ' recursion',
            'is_reserved' => 0,
            'is_active' => 1,
          );
          $opGroup = CRM_Core_BAO_OptionGroup::add($optionGroupParams);
          if ($opGroup->id) {
            $oldWeight = 0;
            $fieldValues = array('option_group_id' => $opGroup->id);
            foreach ($excludeDates as $val) {
              $optionGroupValue = array(
                'option_group_id' => $opGroup->id,
                'label' => CRM_Utils_Date::processDate($val),
                'value' => CRM_Utils_Date::processDate($val),
                'name' => $opGroup->name,
                'description' => 'Used for recurring ' . $type,
                'weight' => CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, CRM_Utils_Array::value('weight', $params), $fieldValues),
                'is_active' => 1,
              );
              $excludeDateList[] = $optionGroupValue['value'];
              CRM_Core_BAO_OptionValue::create($optionGroupValue);
            }
          }
        }

        $recursion = new CRM_AdvancedEvents_BAO_RecurringEntity();
        $recursion->dateColumns = $params['dateColumns'];
        $recursion->scheduleId = $actionScheduleObj->id;

        if (!empty($excludeDateList)) {
          $recursion->excludeDates = $excludeDateList;
          $recursion->excludeDateRangeColumns = $params['excludeDateRangeColumns'];
        }
        if (!empty($params['intervalDateColumns'])) {
          $recursion->intervalDateColumns = $params['intervalDateColumns'];
        }
        $recursion->entity_id = $params['entity_id'];
        $recursion->generate();

        $status = ts('Repeat Configuration has been saved');
        CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
      }
    }
  }

}
