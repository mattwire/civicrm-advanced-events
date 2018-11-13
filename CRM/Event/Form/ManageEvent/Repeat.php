<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CRM_Event_Form_ManageEvent_Repeat extends CRM_Event_Form_ManageEvent {

  /**
   * Parent Event Start Date.
   */
  protected $_parentEventStartDate = NULL;

  /**
   * Parent Event End Date.
   */
  protected $_parentEventEndDate = NULL;

  /**
   * @return int
   */
  protected function getEventId() {
    return $this->_id;
  }

  public function preProcess() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'js/crm.searchForm.js', 1, 'html-header')
      ->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    parent::preProcess();

    if (empty($this->getEventId())) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->assign('templateId', $this->getEventId());

    $sortID = CRM_Utils_Array::value(CRM_Utils_Sort::SORT_ID, $_REQUEST, $this->get(CRM_Utils_Sort::SORT_ID));

    // We "hack" in the event search so we can use it to display/sort events linked to template
    $queryParams = [[
      0 => 'event_template_id',
      1 => '=',
      2 => $this->getEventId(),
      3 => 0,
      4 => 0,
    ]];

    $selector = new CRM_AdvancedEvents_Selector_Search($queryParams);
    $columnHeaders = $selector->getColumnHeaders('query');
    $sort = new CRM_Utils_Sort($columnHeaders, $sortID);
    $selector->query($sort);

    $prefix = NULL;
    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TEMPLATE,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $controller->moveFromSessionToTemplate();

    $this->assign('summary', $this->get('summary'));

    $this->assign('columnHeaders', $selector->getColumnHeaders());
    $this->_rows = $selector->_rows;
    $this->assign('rows', $this->_rows);
    $this->assign('context', 'Search');

    $this->assign("{$prefix}single", $this->_single);

    $parentEventParams = civicrm_api3('Event', 'get', [
      'id' => $this->getEventId(),
      'return' => ['start_date', 'end_date']
    ]);
    if ($parentEventParams['id']) {
      $parentEventParams = $parentEventParams['values'][$parentEventParams['id']];
      $this->_parentEventStartDate = CRM_Utils_Array::value('start_date', $parentEventParams);
      $this->_parentEventEndDate = CRM_Utils_Array::value('end_date', $parentEventParams);
    }
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    // Always pass current event's start date by default
    try {
      $currentEventStartDate = civicrm_api3('Event', 'getvalue', [
        'id' => $this->getEventId(),
        'return' => 'start_date'
      ]);
    } catch (Exception $e) {
      $currentEventStartDate = NULL;
    }
    $defaults['repetition_start_date'] = $currentEventStartDate;
    $recurringEntityDefaults = CRM_AdvancedEvents_Form_RecurringEntity::setDefaultValues();
    return array_merge($defaults, $recurringEntityDefaults);
  }

  public function buildQuickForm() {
    CRM_AdvancedEvents_Form_RecurringEntity::buildQuickForm($this);
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    if ($this->getEventId()) {
      if ($this->_parentEventStartDate && $this->_parentEventEndDate) {
        $interval = CRM_AdvancedEvents_BAO_RecurringEntity::getInterval($this->_parentEventStartDate, $this->_parentEventEndDate);
        $params['intervalDateColumns'] = array('end_date' => $interval);
      }
      $params['dateColumns'] = array('start_date');
      $params['excludeDateRangeColumns'] = array('start_date', 'end_date');
      $params['entity_table'] = 'civicrm_event';
      $params['entity_id'] = $this->getEventId();

      // CRM-16568 - check if parent exist for the event.
      $params['parent_entity_id'] = $params['entity_id'];
      // Unset event id
      unset($params['id']);

      $url = 'civicrm/event/manage/repeat';
      $urlParams = "action=update&reset=1&id={$this->getEventId()}&selectedChild=repeat";

      CRM_AdvancedEvents_Form_RecurringEntity::postProcess($params);
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    else {
      CRM_Core_Error::fatal("Could not find Event ID");
    }

    parent::endPostProcess();
  }

}
