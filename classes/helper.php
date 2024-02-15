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
 * Helper functions for the encoded tool.
 *
 * @package   tool_encoded
 * @author    Benjamin Walker (benjaminwalker@catalyst-au.net)
 * @copyright 2024 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_encoded;

/**
 * Tool encoded helper class.
 */
class helper {
    /**
     * Mapping that helps handle report generation and migrations.
     *
     * @param string $table
     * @param string $column
     * @return array
     */
    public static function get_mapping(string $table, string $column): array {
        GLOBAL $DB;

        $module = $DB->get_record('modules', ['name' => $table]);
        if (isset($module->id) && $column === 'intro') {
            return [
                'component' => 'mod_' . $column,
                'filearea' => 'intro',
                'context' => CONTEXT_MODULE,
                'itemid' => 0,
                'view' => 'course/modedit.php?update=$instanceid',
            ];
        }

        $mapping = [
            'question' => [
                'questiontext' => [
                    'component' => 'question',
                    'filearea' => 'questiontext',
                    'context' => CONTEXT_COURSE,
                    'itemid' => '$id',
                    'view' => 'question/bank/editquestion/question.php?courseid=$instanceid&id=$id',
                ],
                'generalfeedback' => [
                    'component' => 'question',
                    'filearea' => 'generalfeedback',
                    'context' => CONTEXT_COURSE,
                    'itemid' => '$id',
                    'view' => 'question/bank/editquestion/question.php?courseid=$instanceid&id=$id',
                ],
            ],
            'book_chapters' => [
                'content' => [
                    'component' => 'mod_book',
                    'filearea' => 'chapter',
                    'context' => CONTEXT_MODULE,
                    'itemid' => '$id',
                    'view' => 'mod/book/edit.php?cmid=$instanceid&id=$id',
                    'simplejoin' => 'bookid',
                ],
            ],
            'lesson_pages' => [
                'contents' => [
                    'component' => 'mod_lesson',
                    'filearea' => 'page_contents',
                    'context' => CONTEXT_MODULE,
                    'itemid' => '$id',
                    'view' => 'mod/lesson/editpage.php?id=$instanceid&pageid=$id&edit=1',
                    'simplejoin' => 'lessonid',
                ],
            ],
            'workshop_submissions' => [
                'content' => [
                    'component' => 'mod_workshop',
                    'filearea' => 'submission_content',
                    'context' => CONTEXT_MODULE,
                    'itemid' => '$id',
                    'view' => '',
                    'simplejoin' => 'workshopid',
                ]
            ]
        ];
        return $mapping[$table][$column] ?? [];
    }
}
