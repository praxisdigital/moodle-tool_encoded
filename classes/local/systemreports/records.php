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

namespace tool_encoded\local\systemreports;

use context_system;
use lang_string;
use moodle_url;
use pix_icon;
use core_reportbuilder\system_report;
use core_reportbuilder\local\report\action;
use core_admin\reportbuilder\local\entities\task_log;
use tool_encoded\local\entities\records as record_entity;

/**
 * System report class implementation
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class records extends system_report {
    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all the column definitions that we need.
        $entitymain = new record_entity();
        $entitymainalias = $entitymain->get_table_alias('tool_encoded_potential_records');

        $this->set_main_table('tool_encoded_potential_records', $entitymainalias);
        $this->add_entity($entitymain);

        // Any columns required by actions should be defined here to ensure they're always available.
        $this->add_base_fields("{$entitymainalias}.link_fragment");
        $this->add_base_fields("{$entitymainalias}.native_id");

        $entityuser = new task_log();
        $entittlalias = $entityuser->get_table_alias('task_log');
        $this->add_entity($entityuser->add_join(
            "LEFT JOIN {task_log} {$entittlalias} ON {$entittlalias}.pid = {$entitymainalias}.pid"
        ));
        $this->add_base_condition_sql("{$entittlalias}.component = 'tool_encoded'");

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:configview', context_system::instance());
    }

    /**
     * Get the visible name of the report
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('encoderlog', 'admin');
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $this->add_columns_from_entities([
            'records:pid',
            'records:report_table',
            'records:report_columns',
            'records:native_id',
            'records:encoded_size',
            'records:mimetype',
            'records:migrated',
            'task_log:duration'
        ]);
        $this->set_initial_sort_column('records:pid', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $this->add_filters_from_entities([
            'records:report_table',
            'records:report_columns',
            'records:encoded_size',
            'records:mimetype',
            'records:migrated',
            'records:pid'
        ]);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     */
    protected function add_actions(): void {
        // TODO: Only params get the rewrite annoyingly.
        $this->add_action((new action(
            new moodle_url(':link_fragment', ['id' => ':native_id']),
            new pix_icon('t/viewdetails', ''),
            [],
            false,
            new lang_string('view'),
        )));
        $this->add_action((new action(
            new moodle_url('/admin/tool/base64encode/report.php'),
            new pix_icon('i/delete', ''),
            [],
            false,
            new lang_string('delete'),
        )));
    }
}
