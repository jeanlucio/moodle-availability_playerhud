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
 * Tests for the repair of PlayerHUD restrictions saved without their type.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud\local;

use advanced_testcase;

/**
 * Repair of availability trees written by condition::save() before it saved the type.
 *
 * The broken trees are written directly: they are the state left in the database by earlier
 * versions, which no longer produce it.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_playerhud\local\availability_repair
 */
final class availability_repair_test extends advanced_testcase {
    /** @var string An item leaf as the old save() wrote it. */
    private const UNTYPED_ITEM = '{"subtype":"item","levelval":0,"itemid":7,"itemqty":1,"itemop":">=","classid":0}';

    /** @var string A gamification leaf as the old save() wrote it. */
    private const UNTYPED_GAMIFICATION = '{"subtype":"gamification","levelval":0,"itemid":0,"itemqty":1,"itemop":">=","classid":0}';

    /**
     * Creates a page with a raw availability value.
     *
     * @param \stdClass $course Course.
     * @param string $availability Availability JSON stored as is.
     * @return int Course module id.
     */
    private function page_with(\stdClass $course, string $availability): int {
        global $DB;
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $page->cmid]);
        return (int) $page->cmid;
    }

    /**
     * Untyped PlayerHUD leaves get their type back, at any depth, in modules and sections.
     */
    public function test_repairs_untyped_leaves_in_modules_and_sections(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);

        $flat = $this->page_with($course, '{"op":"&","showc":[false],"c":[' . self::UNTYPED_ITEM . ']}');
        $nested = '{"op":"|","show":true,"c":[{"op":"&","c":[' . self::UNTYPED_GAMIFICATION . ',' . self::UNTYPED_ITEM
            . ']},{"op":"&","c":[{"op":"!&","c":[' . self::UNTYPED_GAMIFICATION . ']}]}]}';
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $DB->set_field('course_sections', 'availability', $nested, ['id' => $section->id]);

        $this->assertSame(2, availability_repair::repair_all());

        $tree = json_decode($DB->get_field('course_modules', 'availability', ['id' => $flat]));
        $this->assertSame('playerhud', $tree->c[0]->type);
        $this->assertSame(7, $tree->c[0]->itemid);
        new \core_availability\tree($tree);

        $tree = json_decode($DB->get_field('course_sections', 'availability', ['id' => $section->id]));
        $this->assertSame('playerhud', $tree->c[0]->c[0]->type);
        $this->assertSame('playerhud', $tree->c[0]->c[1]->type);
        $this->assertSame('playerhud', $tree->c[1]->c[0]->c[0]->type);
        new \core_availability\tree($tree);
    }

    /**
     * Valid trees and nodes that are not PlayerHUD leaves stay exactly as they were.
     */
    public function test_leaves_other_trees_untouched(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $valid = '{"op":"&","showc":[true],"c":[{"type":"playerhud","subtype":"item","levelval":0,"itemid":7,'
            . '"itemqty":1,"itemop":">=","classid":0}]}';
        $foreign = '{"op":"&","showc":[true],"c":[{"type":"profile","sf":"email","op":"contains","v":"subtype"}]}';
        // Untyped but without this plugin's field set: not something this plugin wrote.
        $unknown = '{"op":"&","showc":[true],"c":[{"subtype":"item","itemid":7}]}';
        $cms = [$this->page_with($course, $valid), $this->page_with($course, $foreign), $this->page_with($course, $unknown)];

        $this->assertSame(0, availability_repair::repair_all());

        foreach ([$valid, $foreign, $unknown] as $i => $json) {
            $this->assertSame($json, $DB->get_field('course_modules', 'availability', ['id' => $cms[$i]]));
        }
    }

    /**
     * Running the repair again changes nothing.
     */
    public function test_is_idempotent(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->page_with($course, '{"op":"&","showc":[false],"c":[' . self::UNTYPED_ITEM . ']}');

        $this->assertSame(1, availability_repair::repair_all());
        $this->assertSame(0, availability_repair::repair_all());
    }
}
