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
 * Frontend class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud;

/**
 * Frontend class for PlayerHUD availability.
 * Handles the UI aspects: showing the plugin in the list and passing data to JS.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Decides whether this plugin should be available in a given course.
     * Only allows adding this restriction if the PlayerHUD block exists in the course.
     *
     * @param \stdClass $course Course object.
     * @param \cm_info|null $cm Course-module currently being edited.
     * @param \section_info|null $section Section currently being edited.
     * @return bool True if the restriction can be added.
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;

        $context = \context_course::instance($course->id);

        // Checks if there is a block instance in this course.
        return $DB->record_exists('block_instances', [
            'blockname' => 'playerhud',
            'parentcontextid' => $context->id,
        ]);
    }

    /**
     * Pass data to the Javascript form (initInner).
     *
     * @param \stdClass $course Course object.
     * @param \cm_info|null $cm Course-module.
     * @param \section_info|null $section Section info.
     * @return array Parameters passed to JS initInner.
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;

        $context = \context_course::instance($course->id);

        // Fetch the block to get items.
        $block = $DB->get_record('block_instances', [
            'blockname' => 'playerhud',
            'parentcontextid' => $context->id,
        ], 'id', IGNORE_MULTIPLE);

        $items = [];
        if ($block) {
            $records = $DB->get_records('block_playerhud_items', ['blockinstanceid' => $block->id], 'name ASC', 'id, name');
            foreach ($records as $r) {
                $items[] = [
                    'id' => (int)$r->id,
                    'name' => format_string($r->name),
                ];
            }
        }

        // Returns an array. The first element will be the 'items' argument in JS.
        return [$items];
    }

    /**
     * Define strings required by Javascript.
     *
     * @return array List of string identifiers.
     */
    protected function get_javascript_strings() {
        return [
            'empty',
            'option_level',
            'option_item',
            'label_type',
            'label_min_level',
            'label_item_select',
            'label_item_qty',
            'op_more',
            'op_less',
            'op_equal',
        ];
    }
}
