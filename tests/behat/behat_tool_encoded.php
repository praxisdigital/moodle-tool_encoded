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

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Encoded related steps definitions.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_encoded extends behat_base {

    /**
     * Create some data.
     *
     * @Given /^I fill the table "([^"]*)" with:$/
     * @param string $table
     * @param TableNode $data
     */
    public function i_fill_the_table_with(string $table, TableNode $data) {
        global $DB;
        // Insert into table.
        $DB->insert_records($table, $data);
    }

    /**
     * A generate a report for the given table and columns.
     *
     * @Given /^I generate a report for "([^"]*)"$/
     * @param string $table
     */
    public function i_generate_a_report_for_and_columns(string $table) {
        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            "Plugins > Admin tools > Base64 Encoder > Generate report"
        );
        $this->execute('behat_forms::press_button', $table . '_generate');

        $this->execute('behat_general::i_wait_to_be_redirected');
        $this->execute('behat_general::i_trigger_cron');
        $this->execute('behat_general::i_am_on_site_homepage');
    }
}
