<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 5.0                                                |
+---------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+---------------------------------------------------------------------+
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
 +-------------------------------------------------------------------*/

use CRM_AdvancedEvents_ExtensionUtil as E;

class CRM_AdvancedEvents_Functions {

  public static function getEnabled() {
    // 'settings', 'location', 'fee', 'registration', 'reminder', 'friend', 'pcp', 'repeat'
    return [
      'location' => CRM_AdvancedEvents_Settings::getValue('function_location'),
      'fee' => CRM_AdvancedEvents_Settings::getValue('function_fee'),
      'registration' => CRM_AdvancedEvents_Settings::getValue('function_registration'),
      'reminder' => CRM_AdvancedEvents_Settings::getValue('function_reminder'),
      'friend' => CRM_AdvancedEvents_Settings::getValue('function_friend'),
      'pcp' => CRM_AdvancedEvents_Settings::getValue('function_pcp'),
      'repeat' => CRM_AdvancedEvents_Settings::getValue('function_repeat'),
    ];
  }
}