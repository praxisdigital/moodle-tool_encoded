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
use core_reportbuilder\system_report_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    tool_encoded\local\systemreports\records
 */
class records_test extends \advanced_testcase {
    public function test_records() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $records = system_report_factory::create(records::class, context_system::instance());
        $this->assertInstanceOf(records::class, $records);
        $records->get_name();
        $this->assertEquals('Encoder log', $records->get_name());
    }
}
