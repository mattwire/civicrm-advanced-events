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
 * Files required
 */

/**
 * This file is for civievent search
 */
class CRM_AdvancedEvents_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * Prefix for the controller.
   */
  protected $_prefix = "event_";

  /**
   * The saved search ID retrieved from the GET vars.
   *
   * @var int
   */
  protected $_ssID;

  /**
   * Processing needed for buildForm and later.
   *
   * @return void
   */
  public function preProcess() {
    $this->_selectedChild = CRM_Utils_Request::retrieve('selectedChild', 'Alphanumeric', $this);

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */
    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean');
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'search');
    $this->assign("context", $this->_context);

    $this->_templateId = CRM_Utils_Request::retrieve('template_id', 'Positive', $this);

    //set the context for redirection for any task actions
    $session = CRM_Core_Session::singleton();

    if ($this->_templateId) {
      $urlPath = 'civicrm/event/manage/settings';
      $urlParams['id'] = $this->_templateId;
      if ($this->_selectedChild) {
        $urlParams['selectedChild'] = $this->_selectedChild;
      }
    }
    else {
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
      if (CRM_Utils_Rule::qfKey($qfKey)) {
        $urlParams['qfKey'] = $qfKey;
      }
      $urlPath = 'civicrm/events/search';
    }
    $urlParams['reset'] = 1;
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $urlParams['action'] = 'update';
    }
    $url = CRM_Utils_System::url($urlPath, $urlParams);
    $session->replaceUserContext($url);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    if ($this->_force) {
      $this->postProcess();
      $this->set('force', 0);
    }

    // This is a bit of a hack to ensure we reload the "Manage Linked Events" tab after
    //  executing a search task if we ran it from there.
    if ($this->_selectedChild && !$this->_force
      && (CRM_Utils_Array::value('_qf_Search_next_action', $_POST) != 'Go')) {
      CRM_Utils_System::redirect($url);
    }

    $sortID = $this->getSortId();

    $this->setQueryParams();

    $selector = new CRM_AdvancedEvents_Selector_Search($this->_queryParams, $this->_limit);
    $selector->query();
    $this->assign('rows', $selector->_rows);

    $selector->setKey($this->controller->_key);

    $this->assign('context', $this->_context);

    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }
    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $controller->moveFromSessionToTemplate();
    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $this->assign('summary', $this->get('summary'));
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->addEntityRef('event_template_id', ts('Event Template'), array(
      'entity' => 'Event',
      'placeholder' => ts('- any -'),
      'multiple' => 1,
      'select' => array('minimumInputLength' => 0),
      'api' => array(
        'label_field' => 'template_title',
        'params' => array(
          'is_template' => 1,
        ),
        'extra' => ['template_title'],
      ),
    ));

    $rows = $this->get('rows');
    if (is_array($rows)) {
      $this->addRowSelectors($rows);
      foreach ($rows as $row) {
        $eventIds[$row['id']] = $row['id'];
      }
    }
    if (is_array($rows) || !empty($this->_templateId)) {
      $tasks = CRM_AdvancedEvents_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
      $this->addTaskMenu($tasks);
    }

  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post procesing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submissing is
   *        done.
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   *
   * @param
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;
    $this->_formValues = $this->controller->exportValues($this->_name);

    $this->fixFormValues();

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->setQueryParams();

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page

      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }

    $sortID = $this->getSortId();

    $selector = new CRM_AdvancedEvents_Selector_Search($this->_queryParams, $this->_limit);

    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);

    $controller->run();
  }

  private function setQueryParams() {
    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, array('id'));

    if (!empty($this->_templateId)) {
      $this->assign('single', TRUE);
      $this->_queryParams = [[
        0 => 'event_template_id',
        1 => '=',
        2 => $this->_templateId,
        3 => 0,
        4 => 0,
      ]];
    }
  }

  private function getSortId() {
    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }
    return $sortID;
  }
  /**
   * add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @return void
   * @see valid_date
   */
  public function addRules() {
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = $this->_formValues;
    return $defaults;
  }

  public function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)
    $event = CRM_Utils_Request::retrieve('event', 'Positive');
    if ($event) {
      $this->_formValues['id'] = $event;
      $this->_formValues['event_name'] = CRM_Event_PseudoConstant::event($event, TRUE);
    }

    $type = CRM_Utils_Request::retrieve('type', 'Positive');
    if ($type) {
      $this->_formValues['event_type'] = $type;
    }

    $templateId = CRM_Utils_Request::retrieve('template_id', 'Positive', $this);

    if ($templateId) {
      $templateId = CRM_Utils_Type::escape($templateId, 'Positive');
      if ($templateId > 0) {
        $this->_formValues['template_id'] = $templateId;

        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }
  }

  /**
   * @return null
   */
  public function getFormValues() {
    return NULL;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Events');
  }

}
