# CiviCRM Advanced Events Extension

![Example Event Config](/docs/images/event_config_tab.png)

![Advanced Event Settings](/docs/images/advanced_event_settings.png)

This extension provides a number of useful features to complement/improve the CiviEvent component in CiviCRM.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Features

* Ability to hide functionality in the UI that is not required (eg. Tell a Friend, Personal Campaigns).
* Replace existing repeat events functionality with new based directly on Event templates.

## Requirements

* PHP v7.0+
* CiviCRM 5.8+ (see notes below re. earlier versions)
* https://github.com/mattwire/civicrm-core/tree/advanced_events

Core PRs temporarily included in the extension (they should be merged to core eventually):
* https://github.com/civicrm/civicrm-core/pull/12769


### CiviCRM 5.7
Recommended: https://github.com/civicrm/civicrm-core/pull/12747 *without this change some page/form redirects may go to the wrong tab on submit (eg. "Info and Settings" instead of "Repeat")* 

### CiviCRM 5.6
Requires: https://github.com/civicrm/civicrm-core/pull/12781 *Without this change advanced events will not work correctly.*

### CiviCRM 5.5
If using event locations you may require: https://github.com/civicrm/civicrm-core/pull/12459


## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl advanced-events@https://github.com/mattwire/civicrm-advanced-events/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/mattwire/civicrm-advanced-events.git
cv en advanced_events
```

## Usage

Navigate to Administer->CiviEvent->Advanced Events Configuration.

## Known Issues

This extension is under development.
