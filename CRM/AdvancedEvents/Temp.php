<?php

class CRM_AdvancedEvents_Temp {

  /**
   * Make sure that the user has permission to access this event.
   * TODO: Remove once https://github.com/civicrm/civicrm-core/pull/12769 is merged increment min version
   *
   * @param int $eventId
   * @param int $permissionType
   *
   * @return bool|array
   *   Whether the user has permission for this event (or if eventId=NULL an array of permissions)
   * @throws \CiviCRM_API3_Exception
   */
  public static function checkPermission($eventId = NULL, $permissionType = CRM_Core_Permission::VIEW) {
    if (empty($eventId)) {
      // Not used in extension... return self::getAllPermissions($permissionType);
      return FALSE;
    }

    switch ($permissionType) {
      case CRM_Core_Permission::VIEW:
        if (isset(Civi::$statics[__CLASS__]['permission']['view'][$eventId])) {
          return Civi::$statics[__CLASS__]['permission']['view'][$eventId];
        }
        Civi::$statics[__CLASS__]['permission']['view'][$eventId] = FALSE;

        list($allEvents, $createdEvents) = self::checkPermissionGetInfo($eventId);
        if (CRM_Core_Permission::check('access CiviEvent')) {
          if (in_array($eventId, CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_event', $allEvents, array_keys($createdEvents)))) {
            // User created this event so has permission to view it
            return Civi::$statics[__CLASS__]['permission']['view'][$eventId] = TRUE;
          }
          if (CRM_Core_Permission::check('view event participants')) {
            // User has permission to view all events
            // use case: allow "view all events" but NOT "edit all events"
            // so for a normal site allow users with these two permissions to view all events AND
            // at the same time also allow any hook to override if needed.
            if (in_array($eventId, CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_event', $allEvents, array_keys($allEvents)))) {
              Civi::$statics[__CLASS__]['permission']['view'][$eventId] = TRUE;
            }
          }
        }
        return Civi::$statics[__CLASS__]['permission']['view'][$eventId];

      case CRM_Core_Permission::EDIT:
        if (isset(Civi::$statics[__CLASS__]['permission']['edit'][$eventId])) {
          return Civi::$statics[__CLASS__]['permission']['edit'][$eventId];
        }
        Civi::$statics[__CLASS__]['permission']['edit'][$eventId] = FALSE;

        list($allEvents, $createdEvents) = self::checkPermissionGetInfo($eventId);
        // Note: for a multisite setup, a user with edit all events, can edit all events
        // including those from other sites
        if (($permissionType == CRM_Core_Permission::EDIT) && CRM_Core_Permission::check('edit all events')) {
          Civi::$statics[__CLASS__]['permission']['edit'][$eventId] = TRUE;
        }
        elseif (in_array($eventId, CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_event', $allEvents, $createdEvents))) {
          Civi::$statics[__CLASS__]['permission']['edit'][$eventId] = TRUE;
        }
        return Civi::$statics[__CLASS__]['permission']['edit'][$eventId];

      case CRM_Core_Permission::DELETE:
        if (isset(Civi::$statics[__CLASS__]['permission']['delete'][$eventId])) {
          return Civi::$statics[__CLASS__]['permission']['delete'][$eventId];
        }
        Civi::$statics[__CLASS__]['permission']['delete'][$eventId] = FALSE;
        if (CRM_Core_Permission::check('delete in CiviEvent')) {
          Civi::$statics[__CLASS__]['permission']['delete'][$eventId] = TRUE;
        }
        return Civi::$statics[__CLASS__]['permission']['delete'][$eventId];

      default:
        return FALSE;
    }
  }

  /**
   * This is a helper for refactoring checkPermission
   * TODO: Remove once https://github.com/civicrm/civicrm-core/pull/12769 is merged increment min version
   * FIXME: We should be able to get rid of these arrays, but that would require understanding how CRM_ACL_API::group actually works!
   *
   * @param int $eventId
   *
   * @return array $allEvents, $createdEvents
   * @throws \CiviCRM_API3_Exception
   */
  private static function checkPermissionGetInfo($eventId = NULL) {
    $params = [
      'check_permissions' => 1,
      'return' => 'id, created_id',
      'options' => ['limit' => 0],
    ];
    if ($eventId) {
      $params['id'] = $eventId;
    }

    $allEvents = [];
    $createdEvents = [];
    $eventResult = civicrm_api3('Event', 'get', $params);
    if ($eventResult['count'] > 0) {
      $contactId = CRM_Core_Session::getLoggedInContactID();
      foreach ($eventResult['values'] as $eventId => $eventDetail) {
        $allEvents[$eventId] = $eventId;
        if (isset($eventDetail['created_id']) && $contactId == $eventDetail['created_id']) {
          $createdEvents[$eventId] = $eventId;
        }
      }
    }
    return [$allEvents, $createdEvents];
  }
}