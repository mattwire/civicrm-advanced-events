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
 * Class for event form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_AdvancedEvents_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the participant ids.
   *
   * @var array
   */
  protected $_eventIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function preProcessCommon(&$form) {
    $form->_eventIds = array();

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $values['task'];
    $tasks = CRM_Event_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
    if (!array_key_exists($form->_task, $tasks)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    $form->assign('taskName', $tasks[$form->_task]);

    $ids = array();
    if ($values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $eventSearch = new CRM_AdvancedEvents_Selector_Search($queryParams);

      $eventSearch->query();
      $ids = CRM_Utils_Array::collect('id', $eventSearch->_rows);
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_event.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedEvents', count($ids));
    }

    $form->_eventIds = $form->_componentIds = $ids;
  }

  /**
   * Given the participant id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = NULL;
  }

  /**
   * Simple shell that derived classes can call to add buttons to.
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   * @param string $backType
   * @param bool $submitOnce
   *
   * @return void
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons(array(
        array(
          'type' => $nextType,
          'name' => $title,
          'isDefault' => TRUE,
        ),
        array(
          'type' => $backType,
          'name' => ts('Cancel'),
        ),
      )
    );
  }

}
