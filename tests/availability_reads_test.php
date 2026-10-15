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
 * Tests that checking a whole course of PlayerHUD restrictions costs a fixed number of reads.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud;

use advanced_testcase;

/**
 * Database reads of the course-wide availability check.
 *
 * The core calls is_available() once per restricted activity; the condition has to reuse what it
 * already read for the same student instead of querying again for every activity.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_playerhud\condition
 */
final class availability_reads_test extends advanced_testcase {
    /**
     * Creates a course with a PlayerHUD block, one item the student holds and $count restricted pages.
     *
     * @param int $count Number of restricted pages.
     * @param bool $playerhud PlayerHUD item restrictions, or past-date restrictions as the baseline.
     * @param int $userid Student holding the item.
     * @return \stdClass Course.
     */
    private function course_with(int $count, bool $playerhud, int $userid): \stdClass {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($userid, $course->id, 'student');
        $blockid = (int) $this->getDataGenerator()->create_block('playerhud', [
            'parentcontextid' => \context_course::instance($course->id)->id,
        ])->id;
        $DB->set_field('block_instances', 'configdata', base64_encode(serialize(new \stdClass())), ['id' => $blockid]);
        $DB->insert_record('block_playerhud_user', (object) [
            'blockinstanceid' => $blockid, 'userid' => $userid, 'currentxp' => 0, 'enable_gamification' => 1,
            'ranking_visibility' => 1, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $itemid = (int) $DB->insert_record('block_playerhud_items', (object) [
            'blockinstanceid' => $blockid, 'name' => 'Key', 'xp' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('block_playerhud_stack', (object) ['userid' => $userid, 'itemid' => $itemid, 'qty' => 1,
            'timemodified' => time()]);
        $leaf = $playerhud
            ? ['type' => 'playerhud', 'subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 1, 'itemop' => '>=']
            : ['type' => 'date', 'd' => '>=', 't' => time() - DAYSECS];
        for ($i = 0; $i < $count; $i++) {
            $this->getDataGenerator()->create_module('page', [
                'course' => $course->id,
                'availability' => json_encode(['op' => '&', 'c' => [$leaf], 'showc' => [true]]),
            ]);
        }
        return $course;
    }

    /**
     * Reads spent computing every activity's availability for a student, course cache already built.
     *
     * @param \stdClass $course Course.
     * @param int $userid Student.
     * @return int
     */
    private function reads(\stdClass $course, int $userid): int {
        global $DB;
        get_fast_modinfo($course);
        \course_modinfo::clear_instance_cache();
        $before = $DB->perf_get_reads();
        foreach (get_fast_modinfo($course, $userid)->get_cms() as $cm) {
            $this->assertTrue($cm->available);
        }
        return $DB->perf_get_reads() - $before;
    }

    /**
     * The PlayerHUD restrictions add the same few reads whether the course has 3 or 15 of them.
     */
    public function test_reads_do_not_grow_with_the_number_of_restrictions(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        // The first check of a user also loads their capabilities; keep that out of the comparison.
        $this->reads($this->course_with(1, false, $user->id), $user->id);

        $extra = [];
        foreach ([3, 15] as $count) {
            $date = $this->reads($this->course_with($count, false, $user->id), $user->id);
            $playerhud = $this->reads($this->course_with($count, true, $user->id), $user->id);
            $extra[$count] = $playerhud - $date;
        }

        $this->assertLessThanOrEqual(8, $extra[15], 'A whole-course check should need only a handful of reads.');
        $this->assertLessThanOrEqual(1, $extra[15] - $extra[3], 'Reads must not grow with the number of restrictions.');
    }
}
