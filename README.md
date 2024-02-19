# moodle-tool_encoded
An admin tool for Moodle. Intended to provide a way to generate reports for columns that may contain base64 encoded data.

* [Installation](#installation)
* [Usage](#usage)
* [GDPR](#gdpr)
* [Contributing and support](#contributing-and-support)

## Installation
1. Clone this repository into admin/tool/encoded
2. Install the plugins through the moodle GUI
3. Configure the plugin
    1. Configure the size of the files to flag in the report default is 10kb

## Usage
1. Navigate to 'Site admin > Plugins > Admin tools > Base64 Encoder > Generate report'
2. Select a table to generate the report
   1. An option exists to generate reports for every identified table
3. The task will be queued and the report will be generated
   1. A notice will be displayed to inform you that a report will be generated
   2. No feedback is given currently on the status of the task
4. Navigate to 'Site admin > Plugins > Admin tools > Base64 Encoder > Display report'
   1. You can also navigate to this page from a link in the report generation page
5. Any found records will be displayed in a report builder table that can be filtered and sorted

## GDPR
This plugin is GDPR-compliant as it only stores the reference to records and does not restore user data.

Contributing and support
-----------------------
Issues, and pull requests using github are welcome and encouraged!

https://github.com/catalyst/moodle-tool_encoded/issues
