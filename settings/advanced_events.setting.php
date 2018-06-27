<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 +-------------------------------------------------------------------*/

return array(
  'advanced_events_function_location' => array(
    'admin_group' => 'advancedevents_functions',
    'admin_grouptitle' => 'Events Functionality',
    'admin_groupdescription' => 'Enable or Disable event functionality. This does not make any changes to settings for existing events, it just hides the UI elements if disabled.',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_location',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Location',
    'html_attributes' => array(),
  ),

  'advanced_events_function_fee' => array(
    'admin_group' => 'advancedevents_functions',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_fee',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Fees',
    'html_attributes' => array(),
  ),

  'advanced_events_function_registration' => array(
    'admin_group' => 'advancedevents_functions',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_registration',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Online Registration',
    'html_attributes' => array(),
  ),

  'advanced_events_function_reminder' => array(
    'admin_group' => 'advancedevents_functions',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_reminder',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Reminders',
    'html_attributes' => array(),
  ),

  'advanced_events_function_friend' => array(
    'admin_group' => 'advancedevents_functions',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_friend',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Tell A Friend',
    'html_attributes' => array(),
  ),

  'advanced_events_function_pcp' => array(
    'admin_group' => 'advancedevents_functions',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_pcp',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Personal Campaigns',
    'html_attributes' => array(),
  ),

  'advanced_events_function_repeat' => array(
    'admin_group' => 'advancedevents_functions',
    'group_name' => 'Advanced Events Settings',
    'group' => 'advanced_events',
    'name' => 'advanced_events_function_repeat',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 1,
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Repeating Event',
    'html_attributes' => array(),
  ),
);
