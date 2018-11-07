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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 */

/**
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms
 *
 */
class CRM_AdvancedEvents_Task extends CRM_Core_Task {

  static $objectType = 'event';

  const TASK_COPYPARTICIPANTS = 600;

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array The set of tasks for a group of contacts
   *            [ 'title' => The Task title,
   *              'class' => The Task Form class name,
   *              'result => Boolean.  FIXME: Not sure what this is for
   *            ]
   */
  public static function tasks() {
    if (!self::$_tasks) {
      self::$_tasks = array(
        self::TASK_DELETE => array(
          'title' => ts('Delete Events'),
          'class' => 'CRM_AdvancedEvents_Form_Task_Delete',
          'result' => FALSE,
        ),
        /**self::BATCH_UPDATE => array(
          'title' => ts('Update multiple events'),
          'class' => array(
            'CRM_AdvancedEvents_Form_Task_PickProfile',
            'CRM_AdvancedEvents_Form_Task_Batch',
          ),
          'result' => TRUE,
        ),*/
        self::TASK_COPYPARTICIPANTS => array(
          'title' => ts('Copy participants'),
          'class' => 'CRM_AdvancedEvents_Form_Task_CopyParticipants',
          'result' => TRUE,
        ),
      );

      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete in CiviEvent')) {
        unset(self::$_tasks[self::TASK_DELETE]);
      }
      //CRM-12920 - check for edit permission
      if (!CRM_Core_Permission::check('edit event participants')) {
        unset(self::$_tasks[self::BATCH_UPDATE], self::$_tasks[self::TASK_COPYPARTICIPANTS]);
      }

      parent::tasks();
    }

    return self::$_tasks;
  }

  /**
   * Show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   * @param array $params
   *
   * @return array
   *   set of tasks that are valid for the user
   */
  public static function permissionedTaskTitles($permission, $params = array()) {
    if (($permission == CRM_Core_Permission::EDIT)
      || CRM_Core_Permission::check('edit event participants')
    ) {
      $tasks = self::taskTitles();
    }
    else {
      $tasks = array(
        self::TASK_EXPORT => self::$_tasks[self::TASK_EXPORT]['title'],
        self::TASK_EMAIL => self::$_tasks[self::TASK_EMAIL]['title'],
      );

      //CRM-4418,
      if (CRM_Core_Permission::check('delete in CiviEvent')) {
        $tasks[self::TASK_DELETE] = self::$_tasks[self::TASK_DELETE]['title'];
      }
    }

    $tasks = parent::corePermissionedTaskTitles($tasks, $permission, $params);
    return $tasks;
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on participants
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of participants
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = self::TASK_PRINT;
    }
    return parent::getTask($value);
  }

}
