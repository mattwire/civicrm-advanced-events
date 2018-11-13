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
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_AdvancedEvents_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * We use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   */
  static $_properties = array(
    'event_id',
    'event_title',
    'event_start_date',
    'event_end_date',
    'event_type_id',
  );

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * QueryParams is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   */
  public $_queryParams;

  /**
   * The query object.
   *
   * @var string
   */
  protected $_query;

  /**
   * Class constructor.
   *
   * @param array $queryParams
   *   Array of parameters for query.
   * @param \const|int $action - action of search basic or advanced.
   * @param string $eventClause
   *   If the caller wants to further restrict the search (used in events).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many events do we want returned.
   *
   * @param string $context
   * @param null $compContext
   *
   * @return \CRM_AdvancedEvents_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $limit = NULL
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;
    $this->_limit = $limit;
  }

  /**
   * Can be used to alter the number of participation returned from a buildForm hook.
   *
   * @param int $limit
   *   How many participations do we want returned.
   */
  public function setLimit($limit) {
    $this->_limit = $limit;
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @param null $qfKey
   * @param null $context
   * @param null $compContext
   *
   * @return array
   */
  public static function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/event/manage/settings',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Edit Event'),
          'class' => 'no-popup',
          'extra' => 'target="_blank"',
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Event') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    if ($this->_limit) {
      $params['rowCount'] = $this->_limit;
    }
    else {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param int $action
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount($action) {
    $this->query();
    return count($this->_rows);
  }

  /**
   * Function returns the string for the order by clause.
   * @param CRM_Utils_Sort $sort
   * @return string
   *   the order by clause
   */
  public function orderBy($sort) {
    $sortName = $sort->_vars[$sort->getCurrentSortID()]['name'];
    if (empty($sortName)) {
      return '';
    }

    $sortName = str_replace(' ', '_', $sortName);
    $sortName = str_replace('civicrm_event.', '', $sortName);

    if ($sort->_vars[$sort->getCurrentSortID()]['direction'] == CRM_Utils_Sort::ASCENDING ||
      $sort->_vars[$sort->getCurrentSortID()]['direction'] == CRM_Utils_Sort::DONTCARE
    ) {
      return $sortName . ' ASC';
    }
    else {
      return $sortName . ' DESC';
    }
  }

  public function mapQueryParamsToApi() {
    if (isset($this->_apiParams)) {
      return $this->_apiParams;
    }

    $apiParams = [];
    foreach ($this->_queryParams as $queryParamIndex => $queryParam) {
      if ($queryParam[0] == 'entryURL') {
        continue;
      }
      switch ($queryParam[0]) {
        case 'event_start_date_low':
        case 'event_start_date_high':
          $apiParams = array_merge($apiParams, $this->dateQueryBuilder($queryParam,
            'civicrm_event', 'event_start_date', 'start_date', 'Start Date'
          ));
          break;

        case 'event_end_date_low':
        case 'event_end_date_high':
          $apiParams = array_merge($apiParams, $this->dateQueryBuilder($queryParam,
            'civicrm_event', 'event_end_date', 'end_date', 'End Date'
          ));
          break;

        case 'event_template_id':
          if (strpos($queryParam[2], ',') === FALSE) {
            $this->_templateId = $queryParam[2];
            $this->_apiParams['template_id'] = $this->_templateId;
            $templateTitle = civicrm_api3('Event', 'getvalue', ['id' => $queryParam[2], 'return' => 'template_title']);
            $this->_qill['OR'][] = "Template is $templateTitle";
          }
          else {
            $templateIds = explode(',', $queryParam[2]);
            foreach ($templateIds as $templateId) {
              try {
                $templateTitle = civicrm_api3('Event', 'getvalue', [
                  'id' => $templateId,
                  'return' => 'template_title'
                ]);
                $this->_qill['OR'][] = "Template is $templateTitle";
              }
              catch (Exception $e) {}
            }
            $this->templateId = ['IN' => $templateIds];
            $this->_apiParams['template_id'] = $this->_templateId;
          }
          break;

        default:
          switch ($queryParam[1]) {
            case '=':
              $apiParams[$queryParam[0]] = $queryParam[2];
              break;
          }
      }
    }
    $this->_apiParams = $apiParams;
    return $apiParams;
  }

  /**
   * Build query for a date field.
   *
   * @param array $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param string $fieldTitle
   * @param bool $appendTimeStamp
   * @param string $dateFormat
   */
  public function dateQueryBuilder(
    &$values, $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $appendTimeStamp = TRUE,
    $dateFormat = 'YmdHis'
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName]) || !$value) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = $secondDate = $secondDateFormat = NULL;

      if ($name == $fieldName . '_low') {
        $firstOP = '>=';
        $firstPhrase = ts('greater than or equal to');
        $firstDate = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '<=';
          $secondPhrase = ts('less than or equal to');
          $secondValue = $secondValues[2];

          if ($appendTimeStamp && strlen($secondValue) == 10) {
            $secondValue .= ' 23:59:59';
          }
          $secondDate = CRM_Utils_Date::processDate($secondValue, NULL, FALSE, $dateFormat);
        }
      }
      elseif ($name == $fieldName . '_high') {
        $firstOP = '<=';
        $firstPhrase = ts('less than or equal to');

        if ($appendTimeStamp && strlen($value) == 10) {
          $value .= ' 23:59:59';
        }
        $firstDate = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = CRM_Utils_Date::processDate($secondValue, NULL, FALSE, $dateFormat);
        }
      }

      if (!$appendTimeStamp) {
        $firstDate = substr($firstDate, 0, 8);
      }
      $firstDateFormat = CRM_Utils_Date::customFormat($firstDate);

      if ($secondDate) {
        if (!$appendTimeStamp) {
          $secondDate = substr($secondDate, 0, 8);
        }
        $secondDateFormat = CRM_Utils_Date::customFormat($secondDate);
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      if ($secondDate) {
        $apiParams[$dbFieldName] = [$firstOP => $firstDate];
        $apiParams[$dbFieldName] = [$secondOP => $secondDate];
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP '$firstDate' ) AND
( {$tableName}.{$dbFieldName} $secondOP '$secondDate' )
";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\" " . ts('AND') . " $secondPhrase \"$secondDateFormat\"";
      }
      else {
        $apiParams[$dbFieldName] = [$firstOP => $firstDate];
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP '$firstDate'";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\"";
      }
    }

    if ($name == $fieldName) {
      //In Get API, for operators other then '=' the $value is in array(op => value) format
      if (is_array($value) && !empty($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }

      $date = $format = NULL;
      if (strstr($op, 'IN')) {
        $format = array();
        foreach ($value as &$date) {
          $date = CRM_Utils_Date::processDate($date, NULL, FALSE, $dateFormat);
          if (!$appendTimeStamp) {
            $date = substr($date, 0, 8);
          }
          $format[] = CRM_Utils_Date::customFormat($date);
        }
        $date = "('" . implode("','", $value) . "')";
        $format = implode(', ', $format);
      }
      elseif ($value && (!strstr($op, 'NULL') && !strstr($op, 'EMPTY'))) {
        $date = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);
        if (!$appendTimeStamp) {
          $date = substr($date, 0, 8);
        }
        $format = CRM_Utils_Date::customFormat($date);
        $date = "'$date'";
      }

      if ($date) {
        $apiParams[$dbFieldName] = [$op => $date];
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op $date";
      }
      else {
        $apiParams[$dbFieldName] = [$op => 1];
        $this->_where[$grouping][] = self::buildClause("{$tableName}.{$dbFieldName}", $op);
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;

      $op = CRM_Utils_Array::value($op, CRM_Core_SelectValues::getSearchBuilderOperators(), $op);
      $this->_qill[$grouping][] = "$fieldTitle $op $format";
    }
    return $apiParams;
  }

  /**
   * Get where values from the parameters.
   *
   * @param string $name
   * @param mixed $grouping
   *
   * @return mixed
   */
  public function getWhereValues($name, $grouping) {
    $result = NULL;
    foreach ($this->_params as $values) {
      if ($values[0] == $name && $values[3] == $grouping) {
        return $values;
      }
    }

    return $result;
  }

  protected function getEventTemplateId() {
    return isset($this->_templateId) ? $this->_templateId : NULL;
  }

  /**
   * Returns all the rows in the given offset and rowCount.
   *
   * @param string $action
   *   The action being performed.
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   rows in the given offset and rowCount
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    $this->query($sort);
    return $this->_rows;
  }

  public function query($sort = NULL) {
    // When passed in via repeat form we need to store and reload the sort order here,
    // rather than via controller
    if (isset($sort)) {
      $this->sort = $sort;
    }
    elseif (isset($this->sort)) {
      $sort = $this->sort;
    }

    $this->_rows = [];
    $options['limit'] = 0;

    if ($sort) {
      $options['sort'] = $this->orderBy($sort);
    }

    $apiParams = $this->mapQueryParamsToApi();
    $eventParams = array_merge($apiParams, [
      'is_template' => 0,
      'options' => $options,
    ]);

    // Get details of all events linked to this template
    $templateParams = [
      'template_id' => $this->getEventTemplateId(),
      'options' => ['limit' => 0],
    ];

    $eventsLinkedtoTemplate = civicrm_api3('EventTemplate', 'get', $templateParams);
    if (empty($eventsLinkedtoTemplate['count'])) {
      $this->_rows = NULL;
      return;
    }
    $linkedEventIds = CRM_Utils_Array::collect('event_id', $eventsLinkedtoTemplate['values']);
    if (!empty($linkedEventIds)) {
      $eventParams['id'] = ['IN' => $linkedEventIds];
    }

    $rows = civicrm_api3('Event', 'get', $eventParams);
    foreach ($rows['values'] as &$row) {
      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $row['id'];
      $row['event_participant_count'] = civicrm_api3('Participant', 'getcount', ['event_id' => $row['id']]);
      $row['action'] = CRM_Core_Action::formLink(
        self::links(),
        NULL,
        array('id' => $row['id']),
        ts('more'),
        FALSE,
        'event.selector.row',
        'Event',
        $row['id']
      );
      $eventTemplate = civicrm_api3('EventTemplate', 'getsingle', ['event_id' => $row['id'], 'options'=> ['limit' => 1]]);
      $row['template_id'] = $eventTemplate['template_id'];
      $row['template_title'] = $eventTemplate['title'];
      $this->_rows[] = $row;
    }
  }

  /**
   * @inheritDoc
   */
  public function getQILL() {
    if (empty($this->_qill)) {
      return NULL;
    }
    for ($index = 0; $index < count($this->_qill['OR']); $index++) {
      $qill[$index + 1] = $this->_qill['OR'][$index];
    }
    return $qill;
  }

  /**
   * Returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action
   *   The action being performed.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    // Sortable columns need to be numbered weirdly for them to work... (ie 2,1,3 is important)!
    if ($action == NULL) {
      $action = CRM_Core_Action::VIEW;
    }
    if (!isset(self::$_columnHeaders[$action])) {
      self::$_columnHeaders[$action][2] = [
          'name' => ts('Event'),
          'sort' => 'civicrm_event.title',
          'direction' => CRM_Utils_Sort::DONTCARE,
          'weight' => 0,
        ];
      if ($action != 'query') {
        self::$_columnHeaders[$action][6] = [
          'name' => ts('Template'),
          'weight' => 1,
        ];
      }
      self::$_columnHeaders[$action][1] = [
        'name' => ts('Event Date(s)'),
        'sort' => 'civicrm_event.event_start_date',
        'direction' => CRM_Utils_Sort::DESCENDING,
        'weight' => 2,
      ];
      if ($action != 'query') {
        self::$_columnHeaders[$action][4] = [
          'name' => ts('Participants'),
          'weight' => 3,
        ];
      }
      self::$_columnHeaders[$action][3] = [
        'name' => ts('Active'),
        'sort' => 'civicrm_event.is_active',
        'direction' => CRM_Utils_Sort::DONTCARE,
        'weight' => 4,
      ];
      if ($action !== 'query') {
        self::$_columnHeaders[$action][5] = [
          'desc' => ts('Actions'),
          'weight' => 5,
        ];
      }
    }
    return self::$_columnHeaders[$action];
  }

  /**
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   name of the file
   */
  public function getExportFileName($output = 'csv') {
    return ts('CiviCRM Event Search');
  }

}
