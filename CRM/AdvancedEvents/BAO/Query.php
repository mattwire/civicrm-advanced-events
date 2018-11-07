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
class CRM_AdvancedEvents_BAO_Query extends CRM_Core_BAO_Query {

  /**
   * Track open panes, useful in advance search
   *
   * @var array
   */
  public static $_openedPanes = array();

  /**
   * The various search modes.
   *
   * As of February 2017, entries not present for 4, 64, 1024.
   *
   * MODE_ALL seems to be out of sync with the available constants;
   * if this is intentionally excluding MODE_MAILING then that may
   * bear documenting?
   *
   * Likewise if there's reason for the missing modes (4, 64 etc).
   *
   * @var int
   */
  const
    NO_RETURN_PROPERTIES = 'CRM_Contact_BAO_Query::NO_RETURN_PROPERTIES',
    MODE_CONTACTS = 1,
    MODE_CONTRIBUTE = 2,
    // There is no 4,
    MODE_MEMBER = 8,
    MODE_EVENT = 16,
    MODE_CONTACTSRELATED = 32,
    // no 64.
    MODE_GRANT = 128,
    MODE_PLEDGEBANK = 256,
    MODE_PLEDGE = 512,
    // There is no 1024,
    MODE_CASE = 2048,
    MODE_ACTIVITY = 4096,
    MODE_CAMPAIGN = 8192,
    MODE_MAILING = 16384,
    MODE_ALL = 17407;

  /**
   * Constants for search operators
   */
  const
    SEARCH_OPERATOR_AND = 'AND',
    SEARCH_OPERATOR_OR = 'OR';

  /**
   * Class constructor which also does all the work.
   *
   * @param array $params
   * @param array $returnProperties
   * @param array $fields
   * @param bool $skipPermission
   * @param bool $searchDescendentGroups
   * @param bool $smartGroupCache
   * @param null $displayRelationshipType
   * @param string $operator
   * @param string $apiEntity
   * @param bool|NULL $primaryLocationOnly
   */
  public function __construct(
    $params = NULL, $returnProperties = NULL, $fields = NULL,
    $skipPermission = FALSE, $searchDescendentGroups = TRUE,
    $smartGroupCache = TRUE, $displayRelationshipType = NULL,
    $operator = 'AND',
    $apiEntity = NULL
  ) {
    $this->_params = &$params;
    if ($this->_params == NULL) {
      $this->_params = array();
    }

    if ($returnProperties === self::NO_RETURN_PROPERTIES) {
      $this->_returnProperties = array();
    }
    elseif (empty($returnProperties)) {
      $this->_returnProperties = self::defaultReturnProperties();
    }
    else {
      $this->_returnProperties = &$returnProperties;
    }

    $this->_includeContactIds = FALSE;
    $this->_strict = FALSE;
    $this->_mode = CRM_Contact_BAO_Query::MODE_EVENT;
    $this->_skipPermission = $skipPermission;
    $this->_smartGroupCache = $smartGroupCache;
    $this->_displayRelationshipType = $displayRelationshipType;
    $this->setOperator($operator);

    if ($fields) {
      $this->_fields = &$fields;
      $this->_search = FALSE;
      $this->_skipPermission = TRUE;
    }

    // basically do all the work once, and then reuse it
    //$this->initialize($apiEntity);
  }

  /**
   * @param $operator
   */
  public function setOperator($operator) {
    $validOperators = array('AND', 'OR');
    if (!in_array($operator, $validOperators)) {
      $operator = 'AND';
    }
    $this->_operator = $operator;
  }

  /**
   * Function which actually does all the work for the constructor.
   *
   * @param string $apiEntity
   *   The api entity being called.
   *   This sort-of duplicates $mode in a confusing way. Probably not by design.
   */
  public function initialize($apiEntity = NULL) {
    $this->_select = array();
    $this->_element = array();
    $this->_tables = array();
    $this->_whereTables = array();
    $this->_where = array();
    $this->_qill = array();
    $this->_options = array();
    $this->_cfIDs = array();
    $this->_paramLookup = array();
    $this->_having = array();

    $this->_customQuery = NULL;

    if (!empty($this->_params)) {
      $this->buildParamsLookup();
    }

    $this->_whereTables = $this->_tables;

    $this->selectClause($apiEntity);
    $this->_whereClause = $this->whereClause($apiEntity);

    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_mode, $apiEntity);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_mode);

    $this->openedSearchPanes(TRUE);
  }

  /**
   * Function for same purpose as convertFormValues.
   *
   * Like convert form values this function exists to pre-Process parameters from the form.
   *
   * It is unclear why they are different functions & likely relates to advances search
   * versus search builder.
   *
   * The direction we are going is having the form convert values to a standardised format &
   * moving away from weird & wonderful where clause switches.
   *
   * Fix and handle contact deletion nicely.
   *
   * this code is primarily for search builder use case where different clauses can specify if they want deleted.
   *
   * CRM-11971
   */
  public function buildParamsLookup() {
    foreach ($this->_params as $value) {
      if (empty($value[0])) {
        continue;
      }
      $cfID = CRM_Core_BAO_CustomField::getKeyID($value[0]);
      if ($cfID) {
        if (!array_key_exists($cfID, $this->_cfIDs)) {
          $this->_cfIDs[$cfID] = array();
        }
        // Set wildcard value based on "and/or" selection
        foreach ($this->_params as $key => $param) {
          if ($param[0] == $value[0] . '_operator') {
            $value[4] = $param[2] == 'or';
            break;
          }
        }
        $this->_cfIDs[$cfID][] = $value;
      }

      if (!array_key_exists($value[0], $this->_paramLookup)) {
        $this->_paramLookup[$value[0]] = array();
      }
      if ($value[0] !== 'group') {
        // Just trying to unravel how group interacts here! This whole function is weird.
        $this->_paramLookup[$value[0]][] = $value;
      }
    }
  }

  /**
   * Has the pseudoconstant of the field been requested.
   *
   * For example if the field is payment_instrument_id then it
   * has been requested if either payment_instrument_id or payment_instrument
   * have been requested. Payment_instrument is the option groun name field value.
   *
   * @param array $field
   * @param string $fieldName
   *   The unique name of the field - ie. the one it will be aliased to in the query.
   *
   * @return bool
   */
  private function pseudoConstantNameIsInReturnProperties($field, $fieldName = NULL) {
    if (!isset($field['pseudoconstant']['optionGroupName'])) {
      return FALSE;
    }

    if (CRM_Utils_Array::value($field['pseudoconstant']['optionGroupName'], $this->_returnProperties)) {
      return TRUE;
    }
    if (CRM_Utils_Array::value($fieldName, $this->_returnProperties)) {
      return TRUE;
    }
    // Is this still required - the above goes off the unique name. Test with things like
    // communication_prefferences & prefix_id.
    if (CRM_Utils_Array::value($field['name'], $this->_returnProperties)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Given a list of conditions in params and a list of desired
   * return Properties generate the required select and from
   * clauses. Note that since the where clause introduces new
   * tables, the initial attempt also retrieves all variables used
   * in the params list
   *
   * @param string $apiEntity
   *   The api entity being called.
   *   This sort-of duplicates $mode in a confusing way. Probably not by design.
   */
  public function selectClause($apiEntity = NULL) {
    foreach ($this->_fields as $name => $field) {
      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if (
        !empty($this->_paramLookup[$name])
        || !empty($this->_returnProperties[$name])
        || $this->pseudoConstantNameIsInReturnProperties($field)
      ) {
        if ($cfID) {
          // add to cfIDs array if not present
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
        }
      }

      if ($cfID && !empty($field['is_search_range'])) {
        // this is a custom field with range search enabled, so we better check for two/from values
        if (!empty($this->_paramLookup[$name . '_from'])) {
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
          foreach ($this->_paramLookup[$name . '_from'] as $pID => $p) {
            // search in the cdID array for the same grouping
            $fnd = FALSE;
            foreach ($this->_cfIDs[$cfID] as $cID => $c) {
              if ($c[3] == $p[3]) {
                $this->_cfIDs[$cfID][$cID][2]['from'] = $p[2];
                $fnd = TRUE;
              }
            }
            if (!$fnd) {
              $p[2] = array('from' => $p[2]);
              $this->_cfIDs[$cfID][] = $p;
            }
          }
        }
        if (!empty($this->_paramLookup[$name . '_to'])) {
          if (!array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
          }
          foreach ($this->_paramLookup[$name . '_to'] as $pID => $p) {
            // search in the cdID array for the same grouping
            $fnd = FALSE;
            foreach ($this->_cfIDs[$cfID] as $cID => $c) {
              if ($c[4] == $p[4]) {
                $this->_cfIDs[$cfID][$cID][2]['to'] = $p[2];
                $fnd = TRUE;
              }
            }
            if (!$fnd) {
              $p[2] = array('to' => $p[2]);
              $this->_cfIDs[$cfID][] = $p;
            }
          }
        }
      }
    }

    //fix for CRM-951
    //CRM_Core_Component::alterQuery($this, 'select');

    //CRM_Contact_BAO_Query_Hook::singleton()->alterSearchQuery($this, 'select');

    if (!empty($this->_cfIDs)) {
      // @todo This function is the select function but instead of running 'select' it
      // is running the whole query.
      $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs);
      $this->_customQuery->query();
      $this->_select = array_merge($this->_select, $this->_customQuery->_select);
      $this->_element = array_merge($this->_element, $this->_customQuery->_element);
      $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
      $this->_whereTables = array_merge($this->_whereTables, $this->_customQuery->_whereTables);
      $this->_options = $this->_customQuery->_options;
    }
  }

  /**
   * Given a list of conditions in params generate the required where clause.
   *
   * @param string $apiEntity
   *
   * @return string
   */
  public function whereClause($apiEntity = NULL) {
    $this->_where[0] = array();
    $this->_qill[0] = array();

    if (!empty($this->_params)) {
      foreach (array_keys($this->_params) as $id) {
        if (empty($this->_params[$id][0])) {
          continue;
        }
        $this->whereClauseSingle($this->_params[$id], $apiEntity);
      }

      //CRM_Core_Component::alterQuery($this, 'where');

      //CRM_Contact_BAO_Query_Hook::singleton()->alterSearchQuery($this, 'where');
    }

    $clauses = array();
    $andClauses = array();

    $validClauses = 0;
    if (!empty($this->_where)) {
      foreach ($this->_where as $grouping => $values) {
        if ($grouping > 0 && !empty($values)) {
          $clauses[$grouping] = ' ( ' . implode(" {$this->_operator} ", $values) . ' ) ';
          $validClauses++;
        }
      }

      if (!empty($this->_where[0])) {
        $andClauses[] = ' ( ' . implode(" {$this->_operator} ", $this->_where[0]) . ' ) ';
      }
      if (!empty($clauses)) {
        $andClauses[] = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }

    return implode(' AND ', $andClauses);
  }

  /**
   * Create the from clause.
   *
   * @param array $tables
   *   Tables that need to be included in this from clause. If null,
   *   return mimimal from clause (i.e. civicrm_contact).
   * @param array $inner
   *   Tables that should be inner-joined.
   * @param array $right
   *   Tables that should be right-joined.
   * @param bool $primaryLocation
   *   Search on primary location. See note below.
   * @param int $mode
   *   Determines search mode based on bitwise MODE_* constants.
   * @param string|NULL $apiEntity
   *   Determines search mode based on entity by string.
   *
   * The $primaryLocation flag only seems to be used when
   * locationType() has been called. This may be a search option
   * exposed, or perhaps it's a "search all details" approach which
   * predates decoupling of location types and primary fields?
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-19967
   *
   * @return string
   *   the from clause
   */
  public static function fromClause(&$tables, $inner = NULL, $right = NULL, $mode = 1, $apiEntity = NULL) {
    $from = ' FROM civicrm_event';
    if (empty($tables)) {
      return $from;
    }

    // to handle table dependencies of components
    //CRM_Core_Component::tableNames($tables);
    // to handle table dependencies of hook injected tables
    CRM_Contact_BAO_Query_Hook::singleton()->setTableDependency($tables);

    //format the table list according to the weight
    $info = CRM_Core_TableHierarchy::info();

    foreach ($tables as $key => $value) {
      $k = 99;
      if (strpos($key, '-') !== FALSE) {
        $keyArray = explode('-', $key);
        $k = CRM_Utils_Array::value('civicrm_' . $keyArray[1], $info, 99);
      }
      elseif (strpos($key, '_') !== FALSE) {
        $keyArray = explode('_', $key);
        if (is_numeric(array_pop($keyArray))) {
          $k = CRM_Utils_Array::value(implode('_', $keyArray), $info, 99);
        }
        else {
          $k = CRM_Utils_Array::value($key, $info, 99);
        }
      }
      else {
        $k = CRM_Utils_Array::value($key, $info, 99);
      }
      $tempTable[$k . ".$key"] = $key;
    }
    ksort($tempTable);
    $newTables = array();
    foreach ($tempTable as $key) {
      $newTables[$key] = $tables[$key];
    }

    $tables = $newTables;

    foreach ($tables as $name => $value) {
      if (!$value) {
        continue;
      }

      if (!empty($inner[$name])) {
        $side = 'INNER';
      }
      elseif (!empty($right[$name])) {
        $side = 'RIGHT';
      }
      else {
        $side = 'LEFT';
      }

      if ($value != 1) {
        // if there is already a join statement in value, use value itself
        if (strpos($value, 'JOIN')) {
          $from .= " $value ";
        }
        else {
          $from .= " $side JOIN $name ON ( $value ) ";
        }
        continue;
      }

      switch ($name) {
        default:
          $from .= CRM_Core_Component::from($name, $mode, $side);
          $from .= CRM_Contact_BAO_Query_Hook::singleton()->buildSearchfrom($name, $mode, $side);
          continue;
      }
    }
    return $from;
  }

  /**
   * @param bool $reset
   *
   * @return array
   */
  public function openedSearchPanes($reset = FALSE) {
    if (!$reset || empty($this->_whereTables)) {
      return self::$_openedPanes;
    }

    // pane name to table mapper
    $panesMapper = array(
      ts('Events') => 'civicrm_event',
    );
    CRM_Contact_BAO_Query_Hook::singleton()->getPanesMapper($panesMapper);

    foreach (array_keys($this->_whereTables) as $table) {
      if ($panName = array_search($table, $panesMapper)) {
        self::$_openedPanes[$panName] = TRUE;
      }
    }

    return self::$_openedPanes;
  }

  /**
   * Function get the import/export fields for contribution.
   *
   * @param bool $checkPermission
   *
   * @return array
   *   Associative array of contribution fields
   */
  public static function &getFields($checkPermission = TRUE) {
    return array (
      'event_title' =>
        array (
          'name' => 'title',
          'type' => 2,
          'title' => 'Event Title',
          'description' => 'Event Title (e.g. Fall Fundraiser Dinner)',
          'maxlength' => 255,
          'size' => 45,
          'import' => true,
          'where' => 'civicrm_event.title',
          'headerPattern' => '/(event.)?title$/i',
          'dataPattern' => '',
          'export' => true,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 1,
          'html' =>
            array (
              'type' => 'Text',
            ),
        ),
      'event_start_date' =>
        array (
          'name' => 'start_date',
          'type' => 12,
          'title' => 'Event Start Date',
          'description' => 'Date and time that event starts.',
          'import' => true,
          'where' => 'civicrm_event.start_date',
          'headerPattern' => '/^start|(s(tart\\s)?date)$/i',
          'dataPattern' => '',
          'export' => true,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            array (
              'type' => 'Select Date',
              'formatType' => 'activityDateTime',
            ),
        ),
      'event_end_date' =>
        array (
          'name' => 'end_date',
          'type' => 12,
          'title' => 'Event End Date',
          'description' => 'Date and time that event ends. May be NULL if no defined end date/time',
          'import' => true,
          'where' => 'civicrm_event.end_date',
          'headerPattern' => '/^end|(e(nd\\s)?date)$/i',
          'dataPattern' => '',
          'export' => true,
          'table_name' => 'civicrm_event',
          'entity' => 'Event',
          'bao' => 'CRM_Event_BAO_Event',
          'localizable' => 0,
          'html' =>
            array (
              'type' => 'Select Date',
              'formatType' => 'activityDateTime',
            ),
        ),
    );
  }

  /**
   * Build select for CiviEvent.
   *
   * @param $query
   */
  public static function select(&$query) {
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_EVENT) {
      //add event title also if event id is select
      if (!empty($query->_returnProperties['event_id']) || !empty($query->_returnProperties['event_title'])) {
        $query->_select['event_id'] = "civicrm_event.id as event_id";
        $query->_select['event_title'] = "civicrm_event.title as event_title";
        $query->_element['event_id'] = 1;
        $query->_element['event_title'] = 1;
        $query->_tables['civicrm_event'] = 1;
        $query->_whereTables['civicrm_event'] = 1;
      }

      //add start date / end date
      if (!empty($query->_returnProperties['event_start_date'])) {
        $query->_select['event_start_date'] = "civicrm_event.start_date as event_start_date";
        $query->_element['event_start_date'] = 1;
      }

      if (!empty($query->_returnProperties['event_end_date'])) {
        $query->_select['event_end_date'] = "civicrm_event.end_date as event_end_date";
        $query->_element['event_end_date'] = 1;
      }

      //event type
      if (!empty($query->_returnProperties['event_type'])) {
        $query->_select['event_type'] = "event_type.label as event_type";
        $query->_element['event_type'] = 1;
        $query->_tables['event_type'] = 1;
        $query->_whereTables['event_type'] = 1;
      }

      if (!empty($query->_returnProperties['event_type_id'])) {
        $query->_select['event_type_id'] = "event_type.id as event_type_id";
        $query->_element['event_type_id'] = 1;
        $query->_tables['event_type'] = 1;
        $query->_whereTables['event_type'] = 1;
      }
    }
  }


  /**
   * @param $query
   */
  public static function where(&$query) {
    $query->_rowCountClause = " count(civicrm_event.id)";
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 6) == 'event_') {
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * @param $values
   * @param $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    $checkPermission = empty($query->_skipPermission);
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $fields = CRM_Event_BAO_Event::fields();

    switch ($name) {
      case 'event_start_date_low':
      case 'event_start_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_event', 'event_start_date', 'start_date', 'Start Date'
        );
        return;

      case 'event_end_date_low':
      case 'event_end_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_event', 'event_end_date', 'end_date', 'End Date'
        );
        return;

      case 'event_include_repeating_events':
        /**
         * Include Repeating Events
         */
        //Get parent of this event
        $exEventId = '';
        if ($query->_where[$grouping]) {
          foreach ($query->_where[$grouping] as $key => $val) {
            if (strstr($val, 'civicrm_event.id =')) {
              $exEventId = $val;
              $extractEventId = explode(" ", $val);
              $value = $extractEventId[2];
              $where = $query->_where[$grouping][$key];
            }
            elseif (strstr($val, 'civicrm_event.id IN')) {
              //extract the first event id if multiple events are selected
              preg_match('/civicrm_event.id IN \(\"(\d+)/', $val, $matches);
              $value = $matches[1];
              $where = $query->_where[$grouping][$key];
            }
          }
          if ($exEventId) {
            $extractEventId = explode(" ", $exEventId);
            $value = $extractEventId[2];
          }
          elseif (!empty($matches[1])) {
            $value = $matches[1];
          }
          $where = $query->_where[$grouping][$key];
        }
        $thisEventHasParent = CRM_Core_BAO_RecurringEntity::getParentFor($value, 'civicrm_event');
        if ($thisEventHasParent) {
          $getAllConnections = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($thisEventHasParent, 'civicrm_event');
          $allEventIds = array();
          foreach ($getAllConnections as $key => $val) {
            $allEventIds[] = $val['id'];
          }
          if (!empty($allEventIds)) {
            $op = "IN";
            $value = "(" . implode(",", $allEventIds) . ")";
          }
        }
        $query->_where[$grouping][] = "{$where} OR civicrm_event.id $op {$value}";
        $query->_qill[$grouping][] = ts('Include Repeating Events');
        $query->_tables['civicrm_event'] = $query->_whereTables['civicrm_event'] = 1;
        return;

      case 'event_id':
      case 'event_is_public':
      case 'event_type_id':
      case 'event_title':
        $qillName = $name;
        if (in_array($name, array(
            'event_id',
            'event_title',
            'event_is_public',
          )
        )
        ) {
          $name = str_replace('event_', '', $name);
        }
        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_event.$name", $op, $value, $dataType);
        $query->_tables['civicrm_event'] = $query->_whereTables['civicrm_event'] = 1;
        if (!array_key_exists($qillName, $fields)) {
          break;
        }
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Event_DAO_Event', $name, $value, $op, array('check_permission' => $checkPermission));
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $fields[$qillName]['title'], 2 => $op, 3 => $value));
        return;
    }
  }

  /**
   * @param string $name
   * @param $mode
   * @param $side
   *
   * @return null|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_event':
        break;

      case 'event_type':
        $from = " $side JOIN civicrm_option_group option_group_event_type ON (option_group_event_type.name = 'event_type')";
        $from .= " $side JOIN civicrm_option_value event_type ON (civicrm_event.event_type_id = event_type.value AND option_group_event_type.id = event_type.option_group_id ) ";
        break;

    }
    return $from;
  }

  /**
   * Create and query the db for an contact search.
   *
   * @param int $offset
   *   The offset for the query.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string|CRM_Utils_Sort $sort
   *   The order by string.
   * @param bool $count
   *   Is this a count only query ?.
   * @param bool $includeContactIds
   *   Should we include contact ids?.
   * @param bool $sortByChar
   *   If true returns the distinct array of first characters for search results.
   * @param bool $groupContacts
   *   If true, return only the contact ids.
   * @param bool $returnQuery
   *   Should we return the query as a string.
   * @param string $additionalWhereClause
   *   If the caller wants to further restrict the search (used for components).
   * @param null $sortOrder
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   *
   * @param bool $skipOrderAndLimit
   *
   * @return CRM_Core_DAO
   */
  public function searchQuery(
    $offset = 0, $rowCount = 0, $sort = NULL,
    $count = FALSE, $includeContactIds = FALSE,
    $sortByChar = FALSE, $groupContacts = FALSE,
    $returnQuery = FALSE,
    $additionalWhereClause = NULL, $sortOrder = NULL,
    $additionalFromClause = NULL, $skipOrderAndLimit = FALSE
  ) {
    // building the query string
    $order = $orderBy = $limit = '';
    if (!$count) {
      list($order, $additionalFromClause) = $this->prepareOrderBy($sort, $sortByChar, $sortOrder, $additionalFromClause);

      if ($rowCount > 0 && $offset >= 0) {
        $offset = CRM_Utils_Type::escape($offset, 'Int');
        $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');
        $limit = " LIMIT $offset, $rowCount ";
      }
    }

    // CRM-15231
    $this->_sort = $sort;

    //CRM-15967
    $this->includePseudoFieldsJoin($sort);

    list($select, $from, $where, $having) = $this->query($count, $sortByChar, $groupContacts, $onlyDeleted);

    if (!empty($groupByCols)) {
      $groupBy = " GROUP BY " . implode(', ', $groupByCols);
    }

    if ($additionalWhereClause) {
      $where = $where . ' AND ' . $additionalWhereClause;
    }

    //additional from clause should be w/ proper joins.
    if ($additionalFromClause) {
      $from .= "\n" . $additionalFromClause;
    }

    if ($skipOrderAndLimit) {
      $query = "$select $from $where $having $groupBy";
    }
    else {
      $query = "$select $from $where $having $groupBy $order $limit";
    }

    if ($returnQuery) {
      return $query;
    }
    if ($count) {
      return CRM_Core_DAO::singleValueQuery($query);
    }

    $dao = CRM_Core_DAO::executeQuery($query);

    return $dao;
  }

  /**
   * Generate the query based on what type of query we need.
   *
   * @param bool $count
   * @param bool $sortByChar
   * @param bool $groupContacts
   * @param bool $onlyDeleted
   *
   * @return array
   *   sql query parts as an array
   */
  public function query($count = FALSE, $sortByChar = FALSE, $groupContacts = FALSE, $onlyDeleted = FALSE) {
    // build permission clause
    $this->generatePermissionClause($onlyDeleted, $count);

    if ($count) {
      if (isset($this->_rowCountClause)) {
        $select = "SELECT {$this->_rowCountClause}";
      }
      $from = $this->_simpleFromClause;
    }
    else {
      if (!empty($this->_paramLookup['group'])) {

        list($name, $op, $value, $grouping, $wildcard) = $this->_paramLookup['group'][0];

        if (is_array($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $this->_paramLookup['group'][0][1] = key($value);
        }

        // Presumably the lines below come into manage groups screen.
        // make sure there is only one element
        // this is used when we are running under smog and need to know
        // how the contact was added (CRM-1203)
        $groups = (array) CRM_Utils_Array::value($this->_paramLookup['group'][0][1], $this->_paramLookup['group'][0][2], $this->_paramLookup['group'][0][2]);
        if ((count($this->_paramLookup['group']) == 1) &&
          (count($groups) == 1)
        ) {
          $groupId = $groups[0];

          //check if group is saved search
          $group = new CRM_Contact_BAO_Group();
          $group->id = $groupId;
          $group->find(TRUE);

          if (!isset($group->saved_search_id)) {
            $tbName = "`civicrm_group_contact-{$groupId}`";
            // CRM-17254 don't retrieve extra fields if contact_id is specifically requested
            // as this will add load to an intentionally light query.
            // ideally this code would be removed as it appears to be to support CRM-1203
            // and passing in the required returnProperties from the url would
            // make more sense that globally applying the requirements of one form.
            if (($this->_returnProperties != array('contact_id'))) {
              $this->_select['group_contact_id'] = "$tbName.id as group_contact_id";
              $this->_element['group_contact_id'] = 1;
              $this->_select['status'] = "$tbName.status as status";
              $this->_element['status'] = 1;
            }
          }
        }
        $this->_useGroupBy = TRUE;
      }

      $select = $this->getSelect();
      $from = $this->_fromClause;
    }

    $where = '';
    if (!empty($this->_whereClause)) {
      $where = "WHERE {$this->_whereClause}";
    }

    if (!empty($this->_permissionWhereClause) && empty($this->_displayRelationshipType)) {
      if (empty($where)) {
        $where = "WHERE $this->_permissionWhereClause";
      }
      else {
        $where = "$where AND $this->_permissionWhereClause";
      }
    }

    $having = '';
    if (!empty($this->_having)) {
      foreach ($this->_having as $havingSets) {
        foreach ($havingSets as $havingSet) {
          $havingValue[] = $havingSet;
        }
      }
      $having = ' HAVING ' . implode(' AND ', $havingValue);
    }

    // if we are doing a transform, do it here
    // use the $from, $where and $having to get the contact ID
    if ($this->_displayRelationshipType) {
      $this->filterRelatedContacts($from, $where, $having);
    }

    return array($select, $from, $where, $having);
  }

  /**
   * Get Select Clause.
   *
   * @return string
   */
  public function getSelect() {
    $select = "SELECT ";
    $select .= implode(', ', $this->_select);
    return $select;
  }

  /**
   * Populate $this->_permissionWhereClause with permission related clause and update other
   * query related properties.
   *
   * Function calls ACL permission class and hooks to filter the query appropriately
   *
   * Note that these 2 params were in the code when extracted from another function
   * and a second round extraction would be to make them properties of the class
   *
   * @param bool $onlyDeleted
   *   Only get deleted contacts.
   * @param bool $count
   *   Return Count only.
   */
  public function generatePermissionClause($onlyDeleted = FALSE, $count = FALSE) {
    if (!$this->_skipPermission) {
      $this->_permissionWhereClause = CRM_ACL_API::whereClause(
        CRM_Core_Permission::VIEW,
        $this->_tables,
        $this->_whereTables,
        NULL,
        $onlyDeleted,
        $this->_skipDeleteClause
      );

      // regenerate fromClause since permission might have added tables
      if ($this->_permissionWhereClause) {
        //fix for row count in qill (in contribute/membership find)
        if (!$count) {
          $this->_useDistinct = TRUE;
        }
        //CRM-15231
        $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_mode);
        $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_mode);
        // note : this modifies _fromClause and _simpleFromClause
        $this->includePseudoFieldsJoin($this->_sort);
      }
    }
  }

  /**
   * Include pseudo fields LEFT JOIN.
   * @param string|array $sort can be a object or string
   *
   * @return array|NULL
   */
  public function includePseudoFieldsJoin($sort) {
    if (!$sort || empty($this->_pseudoConstantsSelect)) {
      return NULL;
    }
    $sort = is_string($sort) ? $sort : $sort->orderBy();
    $present = array();

    foreach ($this->_pseudoConstantsSelect as $name => $value) {
      if (!empty($value['table'])) {
        $regex = "/({$value['table']}\.|{$name})/";
        if (preg_match($regex, $sort)) {
          $this->_elemnt[$value['element']] = 1;
          $this->_select[$value['element']] = $value['select'];
          $this->_pseudoConstantsSelect[$name]['sorting'] = 1;
          $present[$value['table']] = $value['join'];
        }
      }
    }
    $presentSimpleFrom = $present;

    $presentClause = $presentSimpleFromClause = NULL;
    if (!empty($present)) {
      $presentClause = implode(' ', $present);
    }
    if (!empty($presentSimpleFrom)) {
      $presentSimpleFromClause = implode(' ', $presentSimpleFrom);
    }

    $this->_fromClause = $this->_fromClause . $presentClause;
    $this->_simpleFromClause = $this->_simpleFromClause . $presentSimpleFromClause;

    return array($presentClause, $presentSimpleFromClause);
  }

  /**
   * Parse and assimilate the various sort options.
   *
   * Side-effect: if sorting on a common column from a related table (`city`, `postal_code`,
   * `email`), the related table may be joined automatically.
   *
   * At time of writing, this code is deeply flawed and should be rewritten. For the moment,
   * it's been extracted to a standalone function.
   *
   * @param string|CRM_Utils_Sort $sort
   *   The order by string.
   * @param bool $sortByChar
   *   If true returns the distinct array of first characters for search results.
   * @param null $sortOrder
   *   Who knows? Hu knows. He who knows Hu knows who.
   * @param string $additionalFromClause
   *   Should be clause with proper joins, effective to reduce where clause load.
   * @return array
   *   list(string $orderByClause, string $additionalFromClause).
   */
  protected function prepareOrderBy($sort, $sortByChar, $sortOrder, $additionalFromClause) {
    $order = NULL;
    $orderByArray = array();
    $config = CRM_Core_Config::singleton();
    if ($config->includeOrderByClause) {
      if ($sort) {
        if (is_string($sort)) {
          $orderBy = $sort;
        }
        else {
          $orderBy = trim($sort->orderBy());
        }
        // Deliberately remove the backticks again, as they mess up the evil
        // string munging below. This balanced by re-escaping before use.
        $orderBy = str_replace('`', '', $orderBy);

        if (!empty($orderBy)) {
          // this is special case while searching for
          // change log CRM-1718
          $order = " ORDER BY $orderBy";

          if ($sortOrder) {
            $order .= " $sortOrder";
          }
        }
      }
      else {
        $order = " ORDER BY event_start_date DESC";
      }
    }
    if (!$order && empty($orderByArray)) {
      return array($order, $additionalFromClause);
    }
    // Remove this here & add it at the end for simplicity.
    $order = trim(str_replace('ORDER BY', '', $order));

    // hack for order clause
    if (!empty($orderByArray)) {
      $order = implode(', ', $orderByArray);
    }
    else {
      $orderByArray = explode(',', $order);
    }
    foreach ($orderByArray as $orderByClause) {
      $orderByClauseParts = explode(' ', trim($orderByClause));
      $field = $orderByClauseParts[0];
      $direction = isset($orderByClauseParts[1]) ? $orderByClauseParts[1] : 'asc';

      switch ($field) {

        default:
          $cfID = CRM_Core_BAO_CustomField::getKeyID($field);
          // add to cfIDs array if not present
          if (!empty($cfID) && !array_key_exists($cfID, $this->_cfIDs)) {
            $this->_cfIDs[$cfID] = array();
            $this->_customQuery = new CRM_Core_BAO_CustomQuery($this->_cfIDs, TRUE, $this->_locationSpecificCustomFields);
            $this->_customQuery->query();
            $this->_select = array_merge($this->_select, $this->_customQuery->_select);
            $this->_tables = array_merge($this->_tables, $this->_customQuery->_tables);
          }
          foreach ($this->_pseudoConstantsSelect as $key => $pseudoConstantMetadata) {
            // By replacing the join to the option value table with the mysql construct
            // ORDER BY field('contribution_status_id', 2,1,4)
            // we can remove a join. In the case of the option value join it is
            /// a join known to cause slow queries.
            // @todo cover other pseudoconstant types. Limited to option group ones in the
            // first instance for scope reasons. They require slightly different handling as the column (label)
            // is not declared for them.
            // @todo so far only integer fields are being handled. If we add string fields we need to look at
            // escaping.
            if (isset($pseudoConstantMetadata['pseudoconstant'])
              && isset($pseudoConstantMetadata['pseudoconstant']['optionGroupName'])
              && $field === CRM_Utils_Array::value('optionGroupName', $pseudoConstantMetadata['pseudoconstant'])
            ) {
              $sortedOptions = $pseudoConstantMetadata['bao']::buildOptions($pseudoConstantMetadata['pseudoField'], NULL, array(
                'orderColumn' => 'label',
              ));
              $order = str_replace("$field $direction", "field({$pseudoConstantMetadata['pseudoField']}," . implode(',', array_keys($sortedOptions)) . ") $direction", $order);
            }
            //CRM-12565 add "`" around $field if it is a pseudo constant
            // This appears to be for 'special' fields like locations with appended numbers or hyphens .. maybe.
            if (!empty($pseudoConstantMetadata['element']) && $pseudoConstantMetadata['element'] == $field) {
              $order = str_replace($field, "`{$field}`", $order);
            }
          }
      }
    }

    $this->_fromClause = self::fromClause($this->_tables, NULL, NULL, $this->_mode);
    $this->_simpleFromClause = self::fromClause($this->_whereTables, NULL, NULL, $this->_mode);

    // The above code relies on crazy brittle string manipulation of a peculiarly-encoded ORDER BY
    // clause. But this magic helper which forgivingly reescapes ORDER BY.
    // Note: $sortByChar implies that $order was hard-coded/trusted, so it can do funky things.
    if ($sortByChar) {
      return array(' ORDER BY ' . $order, $additionalFromClause);
    }
    if ($order) {
      $order = CRM_Utils_Type::escape($order, 'MysqlOrderBy');
      return array(' ORDER BY ' . $order, $additionalFromClause);
    }
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties($includeCustomFields = TRUE) {
    $properties = array(
      'event_id' => 1,
      'event_title' => 1,
      'event_start_date' => 1,
      'event_end_date' => 1,
      'event_type' => 1,
    );

    if ($includeCustomFields) {
      // also get all the custom event properties
      $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Event');
      if (!empty($fields)) {
        foreach ($fields as $name => $dontCare) {
          $properties[$name] = 1;
        }
      }
    }

    return $properties;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildSearchForm(&$form) {
    $form->addEntityRef('event_id', ts('Event Name'), array(
        'entity' => 'event',
        'placeholder' => ts('- any -'),
        'multiple' => 1,
        'select' => array('minimumInputLength' => 0),
      )
    );
    $form->addEntityRef('event_type_id', ts('Event Type'), array(
        'entity' => 'option_value',
        'placeholder' => ts('- any -'),
        'select' => array('minimumInputLength' => 0),
        'api' => array(
          'params' => array('option_group_id' => 'event_type'),
        ),
      )
    );
    CRM_Core_Form_Date::buildDateRange($form, 'event', 1, '_start_date_low', '_end_date_high', ts('From'), FALSE);

    $form->addElement('hidden', 'event_date_range_error');
    $form->addFormRule(array('CRM_AdvancedEvents_BAO_Query', 'formRule'), $form);

    $form->addElement('checkbox', "event_include_repeating_events", NULL, ts('Include all events in the %1 series', array(1 => '<em>%1</em>')));

    self::addCustomFormFields($form, array('Event'));

    $form->assign('validCiviEvent', TRUE);
  }

  /**
   * Check if the values in the date range are in correct chronological order.
   *
   * @todo Get this to work with CRM_Utils_Rule::validDateRange
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $form
   *
   * @return bool|array
   */
  public static function formRule($fields, $files, $form) {
    $errors = array();

    if ((empty($fields['event_start_date_low']) || empty($fields['event_end_date_high']))) {
      return TRUE;
    }
    $lowDate = strtotime($fields['event_start_date_low']);
    $highDate = strtotime($fields['event_end_date_high']);

    if ($lowDate > $highDate) {
      $errors['event_date_range_error'] = ts('Please check that your Event Date Range is in correct chronological order.');
    }

    return empty($errors) ? TRUE : $errors;
  }

}
