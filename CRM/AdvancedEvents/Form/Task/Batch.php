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
 * This class provides the functionality for batch profile update for events.
 */
class CRM_AdvancedEvents_Form_Task_Batch extends CRM_AdvancedEvents_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum profile fields that will be displayed.
   */
  protected $_maxFields = 9;

  /**
   * Variable to store redirect path.
   */
  protected $_userContext;

  /**
   * Variable to store previous status id.
   */
  protected $_fromStatusIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    /*
     * initialize the task and row fields
     */
    parent::preProcess();

    //get the contact read only fields to display.
    $readOnlyFields = array_merge(array('sort_name' => ts('Name')),
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );
    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_eventIds,
      'CiviEvent', $returnProperties
    );
    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');
    if (!$ufGroupId) {
      CRM_Core_Error::fatal('ufGroupId is missing');
    }

    $this->_title = ts('Update multiple events') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    CRM_Utils_System::setTitle($this->_title);
    $this->addDefaultButtons(ts('Save'));
    $this->_fields = array();
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = array('File');
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }

      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && $this->_fields[$name]['attributes']['size'] > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Update Events(s)'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_eventIds);

    foreach ($this->_eventIds as $eventId) {
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
        }
        else {
          // handle non custom fields
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $eventId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_Batch_next') {
      CRM_Core_Session::setStatus(ts("File type field(s) in the selected profile are not supported for Update multiple events."), ts('Unsupported Field Type'), 'info');
    }

    $this->addDefaultButtons(ts('Update Events(s)'));
  }

  /**
   * Set default values for the form.
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    $defaults = array();
    foreach ($this->_eventIds as $eventId) {
      $details[$eventId] = array();
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $eventId, 'Event');
    }

    $this->assign('details', $details);
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->exportValues();
    $this->submit($params);
  }

  /**
   * Assign the minimal set of variables to the template.
   */
  public function assignToTemplate() {
    $this->assign('status', TRUE);
  }

  /**
   * @param $params
   */
  public function submit($params) {
    if (isset($params['field'])) {
      foreach ($params['field'] as $key => $value) {

        //check for custom data
        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          $key,
          'Event'
        );
        foreach (array_keys($value) as $fieldName) {
          // Unset the original custom field now that it has been formatting to the 'custom'
          // array as it may not be in the right format for the api as is (notably for
          // multiple checkbox values).
          // @todo extract submit functions on other Batch update classes &
          // extend CRM_Event_Form_Task_BatchTest::testSubmit with a data provider to test them.
          if (substr($fieldName, 0, 7) === 'custom_') {
            unset($value[$fieldName]);
          }
        }

        $value['id'] = $key;
      }
      CRM_Core_Session::setStatus(ts('The updates have been saved.'), ts('Saved'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts('No updates have been saved.'), ts('Not Saved'), 'alert');
    }
  }

}
