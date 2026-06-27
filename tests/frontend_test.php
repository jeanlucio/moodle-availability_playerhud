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

namespace availability_playerhud;

use advanced_testcase;

/**
 * Tests for the PlayerHUD availability frontend.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_playerhud\frontend
 */
final class frontend_test extends advanced_testcase {
    /** @var \stdClass Course object. */
    protected $course;

    /** @var int Block instance ID. */
    protected $instanceid;

    /** @var object Anonymous frontend subclass exposing protected methods. */
    protected $fe;

    /**
     * Set up the testing environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        global $DB;

        $this->course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($this->course->id);

        $bi = new \stdClass();
        $bi->blockname = 'playerhud';
        $bi->parentcontextid = $coursecontext->id;
        $bi->showinsubcontexts = 0;
        $bi->pagetypepattern = 'course-view-*';
        $bi->defaultregion = 'side-pre';
        $bi->defaultweight = 0;
        $bi->configdata = base64_encode(serialize(new \stdClass()));
        $bi->timecreated = time();
        $bi->timemodified = time();
        $this->instanceid = $DB->insert_record('block_instances', $bi);

        // Anonymous subclass exposes protected frontend methods for testing.
        $this->fe = new class extends frontend {
            /**
             * Expose allow_add() for testing.
             *
             * @param \stdClass $course Course object.
             * @return bool
             */
            public function call_allow_add(\stdClass $course): bool {
                return $this->allow_add($course, null, null);
            }

            /**
             * Expose get_javascript_init_params() for testing.
             *
             * @param \stdClass $course Course object.
             * @return array
             */
            public function call_get_javascript_init_params(\stdClass $course): array {
                return $this->get_javascript_init_params($course, null, null);
            }

            /**
             * Expose get_javascript_strings() for testing.
             *
             * @return array
             */
            public function call_get_javascript_strings(): array {
                return $this->get_javascript_strings();
            }
        };
    }

    /**
     * Test allow_add returns true when the PlayerHUD block exists in the course.
     */
    public function test_allow_add_returns_true_when_block_exists(): void {
        $this->assertTrue($this->fe->call_allow_add($this->course));
    }

    /**
     * Test allow_add returns false when the PlayerHUD block is absent from the course.
     */
    public function test_allow_add_returns_false_when_block_missing(): void {
        global $DB;

        $DB->delete_records('block_instances', ['id' => $this->instanceid]);
        $this->assertFalse($this->fe->call_allow_add($this->course));
    }

    /**
     * Test get_javascript_init_params returns items and classes for the course block.
     */
    public function test_get_javascript_init_params_returns_items_and_classes(): void {
        global $DB;

        $item = new \stdClass();
        $item->blockinstanceid = $this->instanceid;
        $item->name = 'Shield';
        $item->xp = 0;
        $item->timecreated = time();
        $item->timemodified = time();
        $DB->insert_record('block_playerhud_items', $item);

        $rpgclass = new \stdClass();
        $rpgclass->blockinstanceid = $this->instanceid;
        $rpgclass->name = 'Paladin';
        $rpgclass->description = 'A holy warrior.';
        $rpgclass->base_hp = 120;
        $rpgclass->timecreated = time();
        $rpgclass->timemodified = time();
        $DB->insert_record('block_playerhud_classes', $rpgclass);

        [$items, $classes] = $this->fe->call_get_javascript_init_params($this->course);

        $this->assertCount(1, $items);
        $this->assertSame('Shield', $items[0]['name']);
        $this->assertCount(1, $classes);
        $this->assertSame('Paladin', $classes[0]['name']);
    }

    /**
     * Test get_javascript_init_params returns empty arrays when no block exists.
     */
    public function test_get_javascript_init_params_no_block(): void {
        global $DB;

        $DB->delete_records('block_instances', ['id' => $this->instanceid]);
        [$items, $classes] = $this->fe->call_get_javascript_init_params($this->course);

        $this->assertEmpty($items);
        $this->assertEmpty($classes);
    }

    /**
     * Test get_javascript_strings returns all expected string identifiers.
     */
    public function test_get_javascript_strings(): void {
        $strings = $this->fe->call_get_javascript_strings();

        $this->assertContains('option_level', $strings);
        $this->assertContains('option_item', $strings);
        $this->assertContains('option_class', $strings);
        $this->assertContains('option_gamification', $strings);
        $this->assertContains('label_type', $strings);
        $this->assertContains('op_atleast', $strings);
    }
}
