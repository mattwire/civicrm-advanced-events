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
<div class="help">{ts}View and create events from this template{/ts}</div>
<div class="crm-block crm-form-block crm-event-manage-repeat-form-block">
  {include file="CRM/Core/Form/RecurringEntity.tpl" recurringFormIsEmbedded=false}
</div>
<div>
  {if $rowsEmpty|| $rows}
  <div class="crm-block crm-content-block">
    {if $rowsEmpty}
      <div class="crm-results-block crm-results-block-empty">
        {include file="CRM/Event/Form/Search/EmptyResults.tpl"}
      </div>
    {/if}

    {if $rows}
      <div class="crm-results-block">
        {* Search request has returned 1 or more matching rows. *}
        {* This section handles form elements for action task select and submit *}

        {* This section displays the rows along and includes the paging controls *}
        <div id='participantSearch' class="crm-event-search-results">
          {include file="CRM/AdvancedEvents/Form/Selector.tpl" context="Search"}
        </div>
        {* END Actions/Results section *}
      </div>
    {/if}

  </div>
  {/if}
</div>