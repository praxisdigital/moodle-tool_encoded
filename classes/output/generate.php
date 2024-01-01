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
 * Show the options to generate a report.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_encoded\output;

use renderable;
use renderer_base;
use templatable;
use tool_encoded\task\generate_report;

class generate implements templatable, renderable {

    protected $potentialtables = [];

    public $template = 'tool_encoded/generate';

    public function __construct() {
        $action = optional_param('testing', '', PARAM_ALPHA);
        if ($action === 'submit') {
            // @codeCoverageIgnoreStart
            $this->handle_submission();
            // @codeCoverageIgnoreEnd
        }
        $this->potentialtables = $this->fetch_tables();
    }

    public function fetch_tables(): array {
        global $DB;

        $potentialtables = [];

        // Cached fetch;
        $tables = $DB->get_tables();
        foreach ($tables as $table) {
            $potentialcolumns = [];
            $tablecols = $DB->get_columns($table);
            $allcols = [];
            foreach ($tablecols as $column) {
                $urlparams = [
                    'action' => 'generate',
                    'testing' => 'submit',
                    'table' => $table,
                ];
                // Only convert columns that are either text or long varchar.
                if ($column->meta_type == 'X' || ($column->meta_type == 'C' && $column->max_length > 255)) {
                    // We only want fields that have an associated format col as they are editable by the user.
                    if (array_key_exists($column->name.'format', $tablecols)) {
                        $allcols[] = $column->name;
                        $urlparams['column'] = $column->name;
                        $link = new \moodle_url('#', $urlparams);
                        $potentialcolumns[] = [
                            'name' => $column->name,
                            'link' => $link->out(false),
                        ];
                    }
                }
            }
            unset($urlparams['column']);
            $urlparams['columns'] = implode(',', $allcols);
            $cal = new \moodle_url('#', $urlparams);
            if (!empty($potentialcolumns)) {
                $potentialtables[$table] = [
                    'name' => $table,
                    'columns' => $potentialcolumns,
                    'colcount' => count($potentialcolumns),
                    'all' => implode(',', $allcols),
                    'alllink' => $cal->out(false),
                ];
            }
        }
        return $potentialtables;
    }

    /**
     * @return void
     * @throws \coding_exception
     * @codeCoverageIgnore TODO: Move this all to a form.
     */
    public function handle_submission() {
        $table = required_param('table', PARAM_NOTAGS);
        $column = optional_param('column', '', PARAM_NOTAGS);
        $columns = optional_param('columns', '', PARAM_NOTAGS);

        $parsedcols = $column !== '' ? $column : $columns;
        generate_report::queue($table, $parsedcols);
    }

    public function export_for_template(renderer_base $output) {
        return [
            'tables' => $this->potentialtables,
            'count' => count($this->potentialtables),
        ];
    }
}
