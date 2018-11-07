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
{if $context EQ 'Search'}
  {include file="CRM/common/pager.tpl" location="top"}
{/if}

{strip}
  <table class="selector row-highlight">
    <thead class="sticky">
    <tr>
      <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
      {foreach from=$columnHeaders item=header}
        <th scope="col">
          {if $header.sort}
            {assign var='key' value=$header.sort}
            {$sort->_response.$key.link}
          {else}
            {$header.name}
          {/if}
        </th>
      {/foreach}
    </tr>
    </thead>

    {counter start=0 skip=1 print=false}
    {foreach from=$rows item=row}
      <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"} crm-event crm-event_{$row.id}">
        {if $context eq 'Search' }
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
        {/if}
        <td class="crm-event_title"><a href="{crmURL p='civicrm/event/info' q="id=`$row.id`&reset=1"}" title="{ts}View event info page{/ts}">{$row.event_title}</a>
          {if $contactId}<br /><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&event=`$row.id`"}" title="{ts}List participants for this event (all statuses){/ts}">({ts}participants{/ts})</a>{/if}
        </td>
        <td class="crm-event_start_date">{$row.event_start_date|crmDate}
          {if $row.event_end_date && $row.event_end_date|date_format:"%Y%m%d" NEQ $row.event_start_date|date_format:"%Y%m%d"}
            <br/>- {$row.event_end_date|crmDate}
          {/if}
        </td>
        <td class="crm-event_participant_count">
          <a class="action-item crm-hover-button crm-popup" href="{crmURL p="civicrm/event/search" q="reset=1&force=1&event=`$row.id`"}">{$row.event_participant_count}</a>
        </td>
        <td class="crm-event_status" id="row_{$row.id}_status">
          {if $row.is_active eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}
        </td>
        <td>{$row.action|replace:'xx':$id}</td>
      </tr>
    {/foreach}
    {* Link to "View all participants" for Dashboard and Contact Summary *}
    {if $limit and $pager->_totalItems GT $limit }
      {if $context EQ 'event_dashboard' }
        <tr class="even-row">
          <td colspan="10"><a href="{crmURL p='civicrm/event/search' q='reset=1'}">&raquo; {ts}Find more event participants{/ts}...</a></td></tr>
        </tr>
      {elseif $context eq 'participant' }
        <tr class="even-row">
          <td colspan="7"><a href="{crmURL p='civicrm/contact/view' q="reset=1&force=1&selectedChild=participant&cid=$contactId"}">&raquo; {ts}View all events for this contact{/ts}...</a></td></tr>
        </tr>
      {/if}
    {/if}
  </table>
{/strip}

{if $context EQ 'Search'}
  {include file="CRM/common/pager.tpl" location="bottom"}
{/if}