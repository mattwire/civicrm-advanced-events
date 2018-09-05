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

/**
 * This class is to build the form for Deleting Group.
 */
class CRM_AdvancedEvents_Form_ManageEvent_Delete extends CRM_Core_Form {

  /**
   * Page title.
   *
   * @var string
   */
  protected $_title;

  /**
   * Event ID
   *
   * @var integer
   */
  public $_id;
  /**
   * Template ID
   *
   * @var integer
   */
  public $_tpl;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive');

    if (!CRM_Event_BAO_Event::checkPermission($this->_id, CRM_Core_Permission::DELETE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->_tpl = CRM_Utils_Request::retrieve('tpl', 'Positive');
    $isTemplate = CRM_Utils_Request::retrieve('istemplate', 'Boolean');
    $this->assign('istemplate', $isTemplate);

    if ($isTemplate) {
      $eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'template_title');
      $this->setPageTitle('Event Template: ' . $eventTitle);
    }
    else {
      $eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'title');
      $this->setPageTitle('Event: ' . $eventTitle);
    }

    CRM_Core_Session::singleton()->replaceUserContext($this->getRedirectUrl());

    $this->checkForParticipants();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->add('hidden', 'id');
    $buttons = array(
      array(
        'type' => 'next',
        'name' => ts('Delete'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );
    $this->addButtons($buttons);
  }

  public function setDefaultValues() {
    $defaults['id'] = $this->_id;
    return $defaults;
  }

  /**
   * Process the form when submitted.
   */
  public function postProcess() {
    $this->checkForParticipants();
    civicrm_api3('Event', 'delete', ['id' => $this->_id]);
    CRM_Core_Session::setStatus(ts("'%1' has been deleted.", array(1 => $this->_title)), ts('Event Deleted'), 'success');
  }

  public function checkForParticipants() {
    $participant = new CRM_Event_DAO_Participant();
    $participant->event_id = $this->_id;

    if ($participant->find()) {
      $searchURL = CRM_Utils_System::url('civicrm/event/search', 'reset=1');
      CRM_Core_Error::statusBounce(ts('This event cannot be deleted because there are participant records linked to it. If you want to delete this event, you must first find the participants linked to this event and delete them. You can use use <a href=\'%1\'> CiviEvent >> Find Participants page </a>.',
        array(1 => $searchURL)
      ), ts('Deletion Error'), 'error');
    }
  }

  public function getRedirectUrl() {
    return CRM_Utils_System::url('civicrm/event/manage/settings', "action=update&id={$this->_tpl}&reset=1&selectedChild=repeat");
  }

}
