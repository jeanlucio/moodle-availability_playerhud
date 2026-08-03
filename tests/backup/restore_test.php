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
 * Backup and restore tests for availability_playerhud.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud\backup;

use advanced_testcase;
use backup;
use backup_controller;
use backup_setting;
use restore_controller;
use restore_dbops;
use stdClass;

/**
 * Tests that a PlayerHUD restriction's itemid/classid are remapped after a course restore.
 *
 * @covers \availability_playerhud\condition
 */
final class restore_test extends advanced_testcase {
    /**
     * Load backup/restore API before any test in this class.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * A restriction on item ownership must point at the restored course's own item after
     * a course restore, not the source course's item id.
     *
     * Uses the '<' operator (as in the security audit's fail-open scenario): a stale itemid
     * makes the inventory count always resolve to 0, so '<' is unconditionally satisfied and
     * the restriction silently stops blocking anyone in the restored course.
     */
    public function test_item_condition_is_remapped_after_restore(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $cm, $olditemid] = $this->create_fixture_with_item();

        $newcourseid = $this->backup_and_restore($course);

        $newcm = $this->get_restored_cm($newcourseid, 'Gated Content');
        $availability = json_decode($newcm->availability);
        $newitemid = (int) $availability->c[0]->itemid;

        $newblock = $this->get_playerhud_block($newcourseid);
        $expecteditemid = (int) $DB->get_field('block_playerhud_items', 'id', [
            'blockinstanceid' => $newblock->id,
            'name' => 'Poção',
        ], MUST_EXIST);

        $this->assertNotSame($olditemid, $newitemid, 'itemid must not still point at the source course.');
        $this->assertSame($expecteditemid, $newitemid, 'itemid must point at the restored course\'s own item.');
    }

    /**
     * A restriction on RPG class assignment must point at the restored course's own class
     * after a course restore, not the source course's class id.
     */
    public function test_class_condition_is_remapped_after_restore(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $cm, $oldclassid] = $this->create_fixture_with_class();

        $newcourseid = $this->backup_and_restore($course);

        $newcm = $this->get_restored_cm($newcourseid, 'Gated Content');
        $availability = json_decode($newcm->availability);
        $newclassid = (int) $availability->c[0]->classid;

        $newblock = $this->get_playerhud_block($newcourseid);
        $expectedclassid = (int) $DB->get_field('block_playerhud_classes', 'id', [
            'blockinstanceid' => $newblock->id,
            'name' => 'Guerreiro',
        ], MUST_EXIST);

        $this->assertNotSame($oldclassid, $newclassid, 'classid must not still point at the source course.');
        $this->assertSame($expectedclassid, $newclassid, 'classid must point at the restored course\'s own class.');
    }

    /**
     * Creates a course with a PlayerHUD block, one item, and an activity restricted to
     * "fewer than 1" of that item (fail-open once the id is stale).
     *
     * @return array{0: stdClass, 1: stdClass, 2: int} [course, course module, item id]
     */
    private function create_fixture_with_item(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $blockinstanceid = $this->create_playerhud_block($course);

        $item = new stdClass();
        $item->blockinstanceid = $blockinstanceid;
        $item->name = 'Poção';
        $item->xp = 0;
        $item->timecreated = time();
        $item->timemodified = time();
        $itemid = $DB->insert_record('block_playerhud_items', $item);

        $availability = json_encode((object) [
            'op' => '&',
            'c' => [(object) [
                'type' => 'playerhud',
                'subtype' => 'item',
                'itemid' => $itemid,
                'itemqty' => 1,
                'itemop' => '<',
            ]],
            'showc' => [true],
        ]);

        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Gated Content',
            'availability' => $availability,
        ]);

        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);

        return [$course, $cm, $itemid];
    }

    /**
     * Creates a course with a PlayerHUD block, one RPG class, and an activity restricted to
     * that class.
     *
     * @return array{0: stdClass, 1: stdClass, 2: int} [course, course module, class id]
     */
    private function create_fixture_with_class(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $blockinstanceid = $this->create_playerhud_block($course);

        $rpgclass = new stdClass();
        $rpgclass->blockinstanceid = $blockinstanceid;
        $rpgclass->name = 'Guerreiro';
        $rpgclass->description = 'A mighty warrior.';
        $rpgclass->base_hp = 100;
        $rpgclass->timecreated = time();
        $rpgclass->timemodified = time();
        $classid = $DB->insert_record('block_playerhud_classes', $rpgclass);

        $availability = json_encode((object) [
            'op' => '&',
            'c' => [(object) [
                'type' => 'playerhud',
                'subtype' => 'class',
                'classid' => $classid,
            ]],
            'showc' => [true],
        ]);

        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Gated Content',
            'availability' => $availability,
        ]);

        $cm = get_coursemodule_from_instance('page', $page->id, $course->id);

        return [$course, $cm, $classid];
    }

    /**
     * Creates a course-level PlayerHUD block instance.
     *
     * @param stdClass $course Course object.
     * @return int The new block instance ID.
     */
    private function create_playerhud_block(stdClass $course): int {
        global $DB;

        $ctx = \context_course::instance($course->id);

        $bi = new stdClass();
        $bi->blockname = 'playerhud';
        $bi->parentcontextid = $ctx->id;
        $bi->showinsubcontexts = 0;
        $bi->pagetypepattern = 'course-view-*';
        $bi->defaultregion = 'side-pre';
        $bi->defaultweight = 0;
        $bi->configdata = base64_encode(serialize(new stdClass()));
        $bi->timecreated = time();
        $bi->timemodified = time();

        return $DB->insert_record('block_instances', $bi);
    }

    /**
     * Finds the PlayerHUD block instance attached to a course.
     *
     * @param int $courseid Course ID.
     * @return stdClass Block instance record.
     */
    private function get_playerhud_block(int $courseid): stdClass {
        global $DB;

        $ctx = \context_course::instance($courseid);

        return $DB->get_record_sql(
            "SELECT bi.* FROM {block_instances} bi
              WHERE bi.blockname = 'playerhud' AND bi.parentcontextid = :ctxid",
            ['ctxid' => $ctx->id],
            MUST_EXIST
        );
    }

    /**
     * Finds the restored 'page' course module by activity name.
     *
     * @param int $courseid Course ID.
     * @param string $name Activity name.
     * @return stdClass Course module record.
     */
    private function get_restored_cm(int $courseid, string $name): stdClass {
        global $DB;

        $page = $DB->get_record('page', ['course' => $courseid, 'name' => $name], '*', MUST_EXIST);

        return get_coursemodule_from_instance('page', $page->id, $courseid);
    }

    /**
     * Backs up a course and restores it to a new course.
     *
     * Uses MODE_IMPORT for the backup step (no ZIP), matching the block/activity settings
     * this plugin relies on (blocks and activities are backed up by default regardless of
     * mode); the restore step still runs as a real MODE_GENERAL/TARGET_NEW_COURSE restore.
     *
     * @param stdClass $srccourse Source course.
     * @return int ID of the newly restored course.
     */
    private function backup_and_restore(stdClass $srccourse): int {
        global $USER, $CFG;

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $srccourse->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );

        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourseid = restore_dbops::create_new_course(
            $srccourse->fullname,
            $srccourse->shortname . '_restore',
            $srccourse->category
        );

        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
