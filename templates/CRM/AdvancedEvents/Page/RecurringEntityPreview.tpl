{*
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
*}
<h3>
  {ts}A new set of Events will be created with the following dates:{/ts}
</h3>
<div class="help">{ts}If an event already exists for the specified dates a new event will not be created and the existing one will not be updated.{/ts}</div>
<table class="display row-highlight">
  <thead><tr>
    <th>#</th>
    <th>{ts}Start date{/ts}</th>
    {if $endDates}<th>{ts}End date{/ts}</th>{/if}
    <th>Already Exists?</th>
  </tr><thead>
  <tbody>
    {foreach from=$dates item="row" key="count"}
      <tr class="{cycle values="odd-row,even-row"} {if $row.exists}disabled{/if}">
        <td>{$count+1}</td>
        <td>{$row.start_date|crmDate}</td>
        {if $endDates}<td>{$row.end_date|crmDate}</td>{/if}
        <td>{if $row.exists}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
      </tr>
    {/foreach}
  </tbody>
</table>
