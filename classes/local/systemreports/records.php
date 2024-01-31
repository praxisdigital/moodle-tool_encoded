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
        $this->add_base_fields("{$entitymainalias}.cmid");

        $entitytl = new task_log();
        $entitytlalias = $entitytl->get_table_alias('task_log');
        $this->add_entity($entitytl->add_join(
            "JOIN {task_log} {$entitytlalias} ON {$entitytlalias}.pid = {$entitymainalias}.pid"
        ));
        // TODO: Add the user id to the encoded table to group by the actual user and not the task runner ID.
        $this->add_base_condition_sql("{$entitytlalias}.component = 'tool_encoded' GROUP BY {$entitytlalias}.userid");

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
        return get_string('encoderlog', 'tool_encoded');
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
            'records:cmid',
            'records:encoded_size',
            'records:mimetype',
            'records:migrated',
            'task_log:duration'
        ]);
        $this->set_initial_sort_column('records:report_table', SORT_ASC);
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
            'records:pid',
            'records:cmid'
        ]);
    }

    public function row_callback(\stdclass $row): void {
        $guessedlink = new moodle_url($row->link_fragment, ['id' => $row->native_id]);
        $row->guessedlink = $guessedlink->out(false);
    }

    // TODO: Update this when unique records are fetched.
    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     */
    protected function add_actions(): void {
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/viewdetails', ''),
            ['data-action' => ':guessedlink'],
            false,
            new lang_string('view'),
        )));
        // Make some educated guesses on what the delete link should contain.
        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('i/delete', ''),
            ['id' => ':nativeid', 'cmid' => ':cmid', 'name' => 'delete', 'value' => true],
            false,
            new lang_string('delete'),
        )));
    }
}
