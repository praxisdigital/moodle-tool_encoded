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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

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
     *
     * @param string $table
     * @param string $columns
     * @return void
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
        $stime = time();

        // Large tables may take time and memory.
        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $records = $this->search_columns();
        // Make a deep clone of the records just in case other functions need the raw data.
        $preppedrecords = $this->extend_records(unserialize(serialize($records)));
        $transaction = $DB->start_delegated_transaction();
        // Delete old report data. Restricting by column isn't neccesary as all relevant columns should be checked.
        $sql = "report_table = :table";
        $params = [
            'table' => $this->get_custom_data()->table,
        ];
        $DB->delete_records_select('tool_encoded_base64_records', $sql, $params);
        $DB->delete_records_select('tool_encoded_base64_tables', $sql, $params);
        $DB->insert_records('tool_encoded_base64_records', $preppedrecords);
        $DB->insert_record('tool_encoded_base64_tables', $this->get_table_record($stime));
        $transaction->allow_commit();
    }

    /**
     * Search the columns of a table for base64 encoded data.
     * @return array
     */
    private function search_columns(): array {
        global $DB;
        $table = $this->get_custom_data()->table;
        $tablecols = array_keys($DB->get_columns($table));
        $columns = implode(',', array_intersect($tablecols, ['id', 'cmid']));

        $sql = "SELECT $columns";
        $params = [];

        // Grabs size and mimetype from base64 columns without the column to avoid memory issues.
        $searchcols = explode(',', $this->get_custom_data()->columns);
        foreach ($searchcols as $col) {
            $paramname = $col . '_pos';
            // Use length as size approximation to avoid loading the full base64 file.
            $sql .= ",LENGTH($col) AS {$col}_size,";
            // Use a simplified query to get the start of a base64 string, process later.
            $sql .= $DB->sql_substr($col, $DB->sql_position(":$paramname", $col), 80) . " AS {$col}_mimetype";
            $params += [$paramname => 'data:'];
        }

        // Add like conditions to find base64 data.
        $sql .= " FROM {{$table}} WHERE ";
        $count = 0;
        foreach ($searchcols as $col) {
            if ($count > 0) {
                $sql .= " OR ";
            }
            $paramname = $col . '_like';
            $sql .= $DB->sql_like($col, ":$paramname");
            $params += [$paramname => '%data:%'];
            $count++;
        }
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Extend the records with additional data.
     *
     * @param array $records The records to extend.
     * @return array
     */
    private function extend_records(array $records): array {
        return array_filter(array_map(function($record) {
            $cleanrecord = new \stdClass();
            // Attempt to get mimetype.
            $base64 = preg_grep('/data:([^"]+)*/', (array) $record);
            foreach ($base64 as $value) {
                preg_match('/data:(.*?);base64/', $value, $matches);
                if (isset($matches[1])) {
                    $cleanrecord->mimetype = $matches[1];
                    break;
                }
            }
            // No matching mimetype found indicates a false positive.
            if (!isset($cleanrecord->mimetype)) {
                return false;
            }
            // Get max column size from provided columns _size field.
            $cleanrecord->encoded_size = max(array_map(function($column) use ($record) {
                return $record->{$column . '_size'} ?? 0;
            }, explode(',', $this->get_custom_data()->columns)));
            $cleanrecord->native_id = (int) $record->id;
            $cleanrecord->pid = $this->get_pid();
            $cleanrecord->report_table = $this->get_custom_data()->table;
            $cleanrecord->report_columns = $this->get_custom_data()->columns;
            $cleanrecord->migrated = 0;
            $cleanrecord->cmid = $record->cmid ?? 0;
            $cleanrecord->link_fragment = $this->link_slug_guess();
            return $cleanrecord;
        }, $records));
    }

    /**
     * Creates a table record object.
     *
     * @param int $stime start time of task
     * @return \stdClass table record
     */
    private function get_table_record(int $stime): \stdClass {
        $tablerecord = new \stdClass();
        $tablerecord->report_table = $this->get_custom_data()->table;
        $tablerecord->report_columns = $this->get_custom_data()->columns;
        $tablerecord->last_checked = $stime;
        $tablerecord->duration = (time() - $stime);
        return $tablerecord;
    }

    /**
     * Take a table name and guess the link slug.
     *
     * @return string
     */
    private function link_slug_guess(): string {
        $table = $this->get_custom_data()->table;
        $tablerename = str_replace('_', '/', $table);
        $guess = strtok($table, '_');
        // This catches alot of the tables, we'll add some manual handling for other common areas manually.
        if (in_array($guess, array_keys(get_module_types_names()))) {
            $slug = '/mod/'.$tablerename.'/view.php';
        } else if (strpos($guess, 'question') || strpos($guess, 'qtype')) {
            // Assume it's a question and try to guess the slug.
            $slug = '/question/edit.php';
        } else if (strpos($guess, 'grade') || strpos($guess, 'grading') || strpos($guess, 'gradingform')) {
            // It might be a grade item so lets go there.
            $slug = '/grade/edit/tree/index.php';
        } else {
            // Throwing everything at the wall and just flat out guessing.
            $slug = '/'.$tablerename;
        }
        return $slug;
    }
}
