{*-------------------------------------------------------------------+
| CiviCRM version 5.0                                                |
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
+-------------------------------------------------------------------*}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <h2>Configuration</h2>

  {foreach from=$elementGroups item=elementGroup}
    <div class="clear">
      <br />
      <h3>{$elementGroup.title}</h3>
      <div class="help">{$elementGroup.description}</div>
      <table class="form-layout-compressed">
        {foreach from=$elementGroup.elementNames item=elementName}
          <tr><td>
            {$form.$elementName.html}
            <label for="{$elementName}">{$form.$elementName.label} {help id=$elementName title=$form.$elementName.label}</label>
            </td></tr>
        {/foreach}
      </table>
    </div>
  {/foreach}

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
