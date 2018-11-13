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
        {if $header.name eq 'Template' && $single}
          {continue}
        {/if}
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
        {if $context eq 'Search'}
          {assign var=cbName value=$row.checkbox}
          <td>{$form.$cbName.html}</td>
        {/if}
        <td class="crm-event_title">
          <a href="{crmURL p='civicrm/event/info' q="id=`$row.id`&reset=1"}" title="{ts}View event info page{/ts}">{$row.event_title}</a>
        </td>
        {if !$single}
        <td class="crm-event_template_title">
          <a href="{crmURL p='civicrm/event/manage/settings' q="id=`$row.template_id`&reset=1"}" title="{ts}Edit event template{/ts}">{$row.template_title}</a>
        </td>
        {/if}
        <td class="crm-event_start_date">
          {if $row.event_end_date && $row.event_end_date|date_format:"%Y%m%d" NEQ $row.event_start_date|date_format:"%Y%m%d"}
            {$row.event_start_date|crmDate}<br/>- {$row.event_end_date|crmDate}
          {elseif $row.event_end_date}
            {$row.event_start_date|crmDate} - {$row.event_end_date|crmDate:0:1}
          {else}
            {$row.event_start_date|crmDate}
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
  </table>
{/strip}

{if $context EQ 'Search'}
  {include file="CRM/common/pager.tpl" location="bottom"}
{/if}
