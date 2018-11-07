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
class CRM_AdvancedEvents_Form_Task_Delete extends CRM_AdvancedEvents_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   * @throws \Exception
   */
  public function preProcess() {

    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviEvent', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Delete Events'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    $deletedEvents = 0;
    foreach ($this->_eventIds as $eventId) {
      // Delete event
      civicrm_api3('Event', 'delete', ['id' => $eventId]);
        $deletedEvents++;
    }

    $status = ts('%count event deleted.', array('plural' => '%count events deleted.', 'count' => $deletedEvents));

    CRM_Core_Session::setStatus($status, ts('Removed'), 'info');
  }

}
