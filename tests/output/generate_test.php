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

namespace tool_encoded\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests.
 *
 * @package   tool_encoded
 * @copyright 2023 Mathew May <mathew.solutions>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \tool_encoded\output\generate
 */
class generate_test extends \advanced_testcase {
    /**
     * Confirm the export and as a bonus, the fetch_tables function for correct data structure.
     *
     * @dataProvider export_for_template_provider
     * @param bool $contains Whether the expected data should exist.
     * @param array $expected The expected tables.
     * @return void
     */
    public function test_export_for_template(bool $contains, array $expected): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $generate = new generate();
        $renderer = $PAGE->get_renderer('core');

        $fetchedtables = $generate->fetch_tables();
        $fetchedcount = count($fetchedtables);
        // Confirm the export for template returns the correct data structure.
        $this->assertEquals([
            'tables' => $fetchedtables,
            'count' => $fetchedcount,
        ], $generate->export_for_template($renderer));

        // Confirm we have reduced the subset of tables to only those with format columns.
        $this->assertLessThan(count($DB->get_tables()), $fetchedcount);

        if($contains) {
            // Some tables that do have format columns, Given on average we have 90+ tables only check a subset.
            foreach ($expected as $table) {
                $this->assertEquals($table, $fetchedtables[$table['name']]);
            }
        } else {
            // Some tables that don't have format columns.
            foreach ($expected as $table) {
                $this->assertArrayNotHasKey($table['name'], $fetchedtables);
            }
        }
    }

    /**
     * Data provider for the test_export_for_template test.
     *
     * @return array
     */
    public function export_for_template_provider(): array {
        return [
            'Contains tables with formatted data.' => [
                true,
                [
                    [
                        'name' => 'book_chapters',
                        'columns' => [
                            [
                                'name' => 'content',
                                'link' => '?action=generate&testing=submit&table=book_chapters&column=content#',
                            ]
                        ],
                        'colcount' => 1,
                        'all' => 'content',
                        'alllink' => '?action=generate&testing=submit&table=book_chapters&columns=content#',
                    ],
                    [
                        'name' => 'workshop_assessments',
                        'columns' => [
                            [
                                'name' => 'feedbackauthor',
                                'link' => '?action=generate&testing=submit&table=workshop_assessments&column=feedbackauthor#',
                            ],
                            [
                                'name' => 'feedbackreviewer',
                                'link' => '?action=generate&testing=submit&table=workshop_assessments&column=feedbackreviewer#',
                            ]
                        ],
                        'colcount' => 2,
                        'all' => 'feedbackauthor,feedbackreviewer',
                        'alllink' => '?action=generate&testing=submit&table=workshop_assessments&columns=feedbackauthor%2Cfeedbackreviewer#',
                    ],
                    [
                        'name' => 'label',
                        'columns' => [
                            [
                                'name' => 'intro',
                                'link' => '?action=generate&testing=submit&table=label&column=intro#',
                            ]
                        ],
                        'colcount' => 1,
                        'all' => 'intro',
                        'alllink' => '?action=generate&testing=submit&table=label&columns=intro#',
                    ],
                    [
                        'name' => 'question_answers',
                        'columns' => [
                            [
                                'name' => 'answer',
                                'link' => '?action=generate&testing=submit&table=question_answers&column=answer#',
                            ],
                            [
                                'name' => 'feedback',
                                'link' => '?action=generate&testing=submit&table=question_answers&column=feedback#',
                            ]
                        ],
                        'colcount' => 2,
                        'all' => 'answer,feedback',
                        'alllink' => '?action=generate&testing=submit&table=question_answers&columns=answer%2Cfeedback#',
                    ],
                    [
                        'name' => 'forum_posts',
                        'columns' => [
                            [
                                'name' => 'message',
                                'link' => '?action=generate&testing=submit&table=forum_posts&column=message#',
                            ]
                        ],
                        'colcount' => 1,
                        'all' => 'message',
                        'alllink' => '?action=generate&testing=submit&table=forum_posts&columns=message#',
                    ],
                ],
            ],
            'Does not contain tables with formatted data.' => [
                false,
                [
                    ['name' => 'tool_usertours_tours'],
                    ['name' => 'assign_submission'],
                    ['name' => 'quiz_attempts'],
                    ['name' => 'forum_discussions'],
                    ['name' => 'workshop_aggregations'],
                    ['name' => 'portfolio_mahara_queue'],
                    ['name' => 'sessions'],
                    ['name' => 'role_capabilities'],
                ],
            ],
        ];
    }
}
