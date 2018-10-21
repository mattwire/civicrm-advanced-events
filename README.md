# CiviCRM Advanced Events Extension

![Example Event Config](/docs/images/event_config_tab.png)

![Advanced Event Settings](/docs/images/advanced_event_settings.png)

This extension provides a number of useful features to complement/improve the CiviEvent component in CiviCRM.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Features

* Ability to hide functionality in the UI that is not required (eg. Tell a Friend, Personal Campaigns).
* Replace existing repeat events functionality with new based directly on Event templates.

## Requirements

* PHP v5.6+
* CiviCRM 5.0+
* https://github.com/mattwire/civicrm-core/tree/advanced_events

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
