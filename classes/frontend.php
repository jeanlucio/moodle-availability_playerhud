<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Frontend class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud;

/**
 * Frontend class for PlayerHUD availability.
 * Handles the UI aspects: showing the plugin in the list and passing data to JS.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
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
     * @return array Parameters passed to JS initInner: items, classes, whether the site runs Bootstrap 5.
     */
    protected function get_javascript_init_params($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        global $CFG, $DB;

        $context = \context_course::instance($course->id);
        $blockid = $DB->get_field('block_instances', 'id', [
            'blockname' => 'playerhud',
            'parentcontextid' => $context->id,
        ], IGNORE_MULTIPLE);

        $items = [];
        $classes = [];
        if ($blockid) {
            // Names go to the form already escaped, the way availability_grade passes its grade items.
            foreach (\block_playerhud\local\external_items::get_names((int) $blockid) as $id => $name) {
                $items[] = ['id' => (int) $id, 'name' => format_string($name, true, ['context' => $context])];
            }
            foreach (\block_playerhud\game::get_class_names((int) $blockid) as $id => $name) {
                $classes[] = ['id' => (int) $id, 'name' => format_string($name, true, ['context' => $context])];
            }
        }

        // Moodle 5.0 moved to Bootstrap 5, where the Bootstrap 4 select classes are deprecated.
        return [$items, $classes, (int) $CFG->branch >= 500];
    }

    /**
     * Define strings required by Javascript.
     *
     * @return array List of string identifiers.
     */
    protected function get_javascript_strings() {
        return [
            'empty',
            'error_selectclass',
            'error_selectitem',
            'option_gamification',
            'option_level',
            'option_item',
            'option_class',
            'label_type',
            'label_min_level',
            'label_item_op',
            'label_item_select',
            'label_item_qty',
            'label_class_select',
            'op_atleast',
            'op_more',
            'op_less',
            'op_equal',
        ];
    }
}
