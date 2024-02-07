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
use tool_encoded\local\helper;

/**
 * Initialise the page that provides the options to generate a report
 */
class generate implements templatable, renderable {

    /**
     * Tables that could potentially have problematic data.
     *
     * @var array
     */
    protected $potentialtables = [];

    /**
     * Initalise and set tables
     */
    public function __construct() {
        $this->potentialtables = $this->fetch_tables();
    }

    /**
     * Fetches the tables that a report can be generated on
     *
     * @return array potential tables
     */
    public function fetch_tables(): array {
        $link = new \moodle_url('/admin/tool/encoded/index.php');
        return helper::getpotentialtables($link);
    }

    /**
     * Export the data
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'tables' => array_values($this->potentialtables),
            'count' => count($this->potentialtables),
            'sesskey' => sesskey(),
        ];
    }
}
