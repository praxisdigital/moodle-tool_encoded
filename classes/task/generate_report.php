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

namespace tool_encoded\task;

use core\task\adhoc_task;
use core\task\manager;

/**
 * Given some columns to migrate, this task will generate a report of potential bad data.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_report extends adhoc_task {
    /**
     * Queue the task for the next run.
     */
    public static function queue(string $table, string $columns): void {
        $task = new self();
        $task->set_custom_data([
            'table' => $table,
            'columns' => $columns,
        ]);
        // Queue the task for the next run.
        manager::queue_adhoc_task($task);
    }

    /**
     * Perform the requested operation.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;
        // Initialize the custom data operation to be used for the action.
        $data = $this->get_custom_data();
        $records = $this->search_columns($data->table, $data->columns);
        // Make a deep clone of the records just in case other functions need the raw data.
        $preppedrecords = $this->extend_records(unserialize(serialize($records)));
        $DB->insert_records('tool_encoded_potential_records', $preppedrecords);
    }

    /**
     * Search the columns of a table for base64 encoded data.
     *
     * @param string $table The table to search.
     * @param string $columns The columns to search.
     * @return array
     */
    private function search_columns(string $table, string $columns): array {
        global $DB;
        // Get the content of the requested table return only columns and an ID and attempt the cmid.
        // TODO: Add CMID.
        $records = $DB->get_records($table, null, null, 'id,'.$columns);

        return array_filter($records, function($record) {
            return preg_grep('/data:([^"]+)*/', (array) $record);
        });
    }

    /**
     * Extend the records with additional data.
     *
     * @param array $records The records to extend.
     * @return array
     */
    private function extend_records(array $records): array {
        return array_map(function($record) {
            $cleanrecord = new \stdClass();
            // TODO: Improve regex.
            $base64 = preg_grep('/data:([^"]+)*/', (array) $record);
            // Future improvement: Handle the case where there are multiple base64 matches.
            foreach ($base64 as $value) {
                preg_match('/data:(.*?);/', $value,$matches);
                $cleanrecord->encoded_size = strlen(base64_decode($value));
                $cleanrecord->mimetype = $matches[0];
            }
            $cleanrecord->native_id = (int) $record->id;
            $cleanrecord->pid = $this->get_pid() ?? 0; // 0 indicates testing.
            $cleanrecord->report_table = $this->get_custom_data()->table;
            $cleanrecord->report_columns = $this->get_custom_data()->columns;
            $cleanrecord->migrated = 0;
            $cleanrecord->cmid = $record->cmid ?? 0;

            //$context = \context::instance_by_id($record->cmid);
            //$cleanrecord->link_fragment = match ($context->get_context_name()) {
            //    'course_section', 'course_category', 'course' => $this->link_slug_guess($this->get_custom_data()->table, 'course'),
            //    'course_module' => $this->link_slug_guess($this->get_custom_data()->table, 'mod'),
            //    'user' => $this->link_slug_guess($this->get_custom_data()->table, 'user'),
            //    default => $this->link_slug_guess($this->get_custom_data()->table),
            //};
            $this->link_slug_guess($this->get_custom_data()->table);
            return $cleanrecord;
        }, $records);
    }

    /**
     * Take a table name and guess the link slug.
     *
     * @param string $table The table name.
     * @return string
     */
    private function link_slug_guess(string $table, $level = ''): string {
        $table = str_replace('_', '/', $table);
        $slug = '/'.$table;
        return $slug;
    }
}
