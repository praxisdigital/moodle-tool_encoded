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
use stdClass;

/**
 * Given our found records, this task will attempt to migrate the data.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate extends adhoc_task {
    /**
     * Queue the task for the next run.
     */
    public static function queue(): void {
        $task = new self();
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
        // TODO: Add condition for only selected records.
        $records = $DB->get_records('tool_encoded_potential_records', null, null);
        foreach ($records as $record) {
            $record->migrated = $this->migrate_record($record);
            // Update the state of the record to indicate it has been migrated.
            $DB->update_record('tool_encoded_potential_records', $record);
        }
    }

    /**
     * Migrate the record.
     *
     * @param stdClass $record
     * @return bool
     */
    private function migrate_record(stdClass $record): bool {
        global $DB;
        $success = false;
        // Find the associated table and columns to attempt to replace data within.
        $storedrecord = $DB->get_record($record->report_table, ['id' => $record->native_id]);
        // Decode the encoded data.
        foreach ($record->report_columns as $column) {
            $decodeddata = base64_decode($storedrecord->{$column});
            // Write the decoded data into a plugin file.

            // Set the column to the link to the file.
            $storedrecord->{$column} = $decodeddata;
            if ($DB->update_record($record->report_table, $storedrecord)) {
                $success = true;
            } else {
                $success = false;
            }
        }

        if ($success) {
            // File was written successfully and the record updated.
            return true;
        }

        // File was not written successfully and there was an issue.
        return false;
    }

}
