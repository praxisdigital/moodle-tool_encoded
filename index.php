<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin tool base64encode landing page.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_reportbuilder\system_report_factory;
use tool_encoded\local\systemreports\records;

require_once(__DIR__ . '/../../../config.php');

$action = optional_param('action', 'report', PARAM_ALPHA);

require_login();

if (!$context = context_system::instance()) {
    throw new moodle_exception('wrongcontext', 'error');
}

require_capability('moodle/site:config', $context);

// Loads the required action class and form.
$classname = 'tool_encoded\\output\\'.$action;

if (!class_exists($classname) && $action !== 'report') {
    throw new moodle_exception('falseaction', 'tool_encoded', $action);
}

// Display the page.
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('base');
// TODO: Change title and heading.
$PAGE->set_title('title example');
$PAGE->set_heading('heading example');
$url = new moodle_url('/admin/tool/encoded/index.php');
$PAGE->set_url($url);

echo $OUTPUT->header();
if ($action === 'report') {
    $report = system_report_factory::create(records::class, context_system::instance());
    echo $report->output();
} else {
    // Executes the required action.
    $instance = new $classname();
    // Example of way to load different functionality based on the desired action.
    echo $OUTPUT->render_from_template($instance->template, $instance->export_for_template($OUTPUT));
}

echo $OUTPUT->footer();
