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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
require_once(__DIR__ . '/fixtures/condition_test_gadget.php');

/**
 * Tests for the PlayerHUD availability condition.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_playerhud\condition
 */
final class condition_test extends advanced_testcase {
    /** @var \stdClass Course object. */
    protected $course;

    /** @var int Block instance ID. */
    protected $instanceid;

    /**
     * Setup the testing environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        global $DB;

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($this->course->id);

        // Add the PlayerHUD block to the course.
        $bi = new \stdClass();
        $bi->blockname = 'playerhud';
        $bi->parentcontextid = $coursecontext->id;
        $bi->showinsubcontexts = 0;
        $bi->pagetypepattern = 'course-view-*';
        $bi->defaultregion = 'side-pre';
        $bi->defaultweight = 0;

        $config = new \stdClass();
        $config->xp_per_level = 100; // Force explicitly for tests.

        $bi->configdata = base64_encode(serialize($config));
        $bi->timecreated = time();
        $bi->timemodified = time();

        $this->instanceid = $DB->insert_record('block_instances', $bi);
    }

    /**
     * Create a dummy user and a player profile with specific XP.
     *
     * @param int $xp The XP amount.
     * @return \stdClass The user object.
     */
    protected function create_player_with_xp(int $xp): \stdClass {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $player = new \stdClass();
        $player->blockinstanceid = $this->instanceid;
        $player->userid = $user->id;
        $player->currentxp = $xp;
        $player->enable_gamification = 1;
        $player->ranking_visibility = 1;
        $player->timecreated = time();
        $player->timemodified = time();

        $DB->insert_record('block_playerhud_user', $player);

        return $user;
    }

    /**
     * Helper to give items to a user.
     *
     * @param int $userid The user ID.
     * @param int $qty The amount to give.
     * @return int The item ID.
     */
    protected function create_and_give_item(int $userid, int $qty): int {
        global $DB;

        // 1. Create Item.
        $item = new \stdClass();
        $item->blockinstanceid = $this->instanceid;
        $item->name = 'Magic Key';
        $item->xp = 0;
        $item->timecreated = time();
        $item->timemodified = time();
        $itemid = $DB->insert_record('block_playerhud_items', $item);

        // 2. Give to user in inventory.
        $records = [];
        for ($i = 0; $i < $qty; $i++) {
            $records[] = (object)[
                'userid' => $userid,
                'itemid' => $itemid,
                'timecreated' => time(),
                'source' => 'test',
                'dropid' => 0,
            ];
        }

        if (!empty($records)) {
            $DB->insert_records('block_playerhud_inventory', $records);
        }

        return $itemid;
    }

    /**
     * Test the fallback when the block is completely missing from the course.
     */
    public function test_is_available_no_block(): void {
        global $DB;

        // Remove the block to simulate a course without PlayerHUD.
        $DB->delete_records('block_instances', ['id' => $this->instanceid]);

        $user = $this->getDataGenerator()->create_user();
        $info = new \core_availability\mock_info($this->course, $user->id);

        $structure = (object)['subtype' => 'level', 'levelval' => 1];
        $cond = new condition($structure);

        // Even for level 1, if the block is missing, it should return false to prevent crashes.
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
    }

    /**
     * Test restriction based on minimum Level.
     */
    public function test_is_available_level(): void {
        // User with 250 XP (Level 3 if xp_per_level is 100).
        $user = $this->create_player_with_xp(250);
        $info = new \core_availability\mock_info($this->course, $user->id);

        // Condition: Requires Level 2.
        $cond2 = new condition((object)['subtype' => 'level', 'levelval' => 2]);
        $this->assertTrue($cond2->is_available(false, $info, true, $user->id));

        // Condition: Requires Level 3 (Exact match).
        $cond3 = new condition((object)['subtype' => 'level', 'levelval' => 3]);
        $this->assertTrue($cond3->is_available(false, $info, true, $user->id));

        // Condition: Requires Level 4 (Should be blocked).
        $cond4 = new condition((object)['subtype' => 'level', 'levelval' => 4]);
        $this->assertFalse($cond4->is_available(false, $info, true, $user->id));
    }

    /**
     * Test restriction based on collected Items with all logical operators.
     */
    public function test_is_available_item(): void {
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);

        // User receives exactly 3 Magic Keys.
        $itemid = $this->create_and_give_item($user->id, 3);

        // 1. Operator: >= (At least 3 -> True, At least 4 -> False)
        $condgtepass = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 3, 'itemop' => '>=']);
        $this->assertTrue($condgtepass->is_available(false, $info, true, $user->id));

        $condgtefail = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 4, 'itemop' => '>=']);
        $this->assertFalse($condgtefail->is_available(false, $info, true, $user->id));

        // 2. Operator: > (More than 2 -> True, More than 3 -> False)
        $condgtpass = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 2, 'itemop' => '>']);
        $this->assertTrue($condgtpass->is_available(false, $info, true, $user->id));

        $condgtfail = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 3, 'itemop' => '>']);
        $this->assertFalse($condgtfail->is_available(false, $info, true, $user->id));

        // 3. Operator: < (Less than 4 -> True, Less than 3 -> False)
        $condltpass = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 4, 'itemop' => '<']);
        $this->assertTrue($condltpass->is_available(false, $info, true, $user->id));

        $condltfail = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 3, 'itemop' => '<']);
        $this->assertFalse($condltfail->is_available(false, $info, true, $user->id));

        // 4. Operator: = (Exactly 3 -> True, Exactly 2 -> False)
        $condeqpass = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 3, 'itemop' => '=']);
        $this->assertTrue($condeqpass->is_available(false, $info, true, $user->id));

        $condeqfail = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 2, 'itemop' => '=']);
        $this->assertFalse($condeqfail->is_available(false, $info, true, $user->id));
    }

    /**
     * Helper to create an RPG class and assign it to a user.
     *
     * @param int $userid The user ID.
     * @return int The class ID.
     */
    protected function create_and_assign_class(int $userid): int {
        global $DB;

        $rpgclass = new \stdClass();
        $rpgclass->blockinstanceid = $this->instanceid;
        $rpgclass->name = 'Warrior';
        $rpgclass->description = 'A mighty warrior.';
        $rpgclass->base_hp = 100;
        $rpgclass->timecreated = time();
        $rpgclass->timemodified = time();
        $classid = $DB->insert_record('block_playerhud_classes', $rpgclass);

        $progress = new \stdClass();
        $progress->blockinstanceid = $this->instanceid;
        $progress->userid = $userid;
        $progress->classid = $classid;
        $progress->karma = 0;
        $progress->current_nodes = '[]';
        $progress->completed_chapters = '[]';
        $progress->timecreated = time();
        $progress->timemodified = time();
        $DB->insert_record('block_playerhud_rpg_progress', $progress);

        return $classid;
    }

    /**
     * Test restriction based on RPG Class.
     */
    public function test_is_available_class(): void {
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);

        $classid = $this->create_and_assign_class($user->id);

        // User has the class: should pass.
        $condpass = new condition((object)['subtype' => 'class', 'classid' => $classid]);
        $this->assertTrue($condpass->is_available(false, $info, true, $user->id));

        // User does not have a different class: should fail.
        $condfail = new condition((object)['subtype' => 'class', 'classid' => $classid + 9999]);
        $this->assertFalse($condfail->is_available(false, $info, true, $user->id));

        // Classid = 0 is always false (no class selected).
        $condzero = new condition((object)['subtype' => 'class', 'classid' => 0]);
        $this->assertFalse($condzero->is_available(false, $info, true, $user->id));
    }

    /**
     * Test that a user without any class assigned fails the class restriction.
     */
    public function test_is_available_class_no_progress(): void {
        global $DB;

        $classid = 42;
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);

        // Create the class record in DB but do NOT assign to user.
        $rpgclass = new \stdClass();
        $rpgclass->blockinstanceid = $this->instanceid;
        $rpgclass->name = 'Mage';
        $rpgclass->description = 'A powerful mage.';
        $rpgclass->base_hp = 60;
        $rpgclass->timecreated = time();
        $rpgclass->timemodified = time();
        $classid = $DB->insert_record('block_playerhud_classes', $rpgclass);

        $cond = new condition((object)['subtype' => 'class', 'classid' => $classid]);
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
    }

    /**
     * Test the description strings returned by the condition.
     */
    public function test_get_description(): void {
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);

        $itemid = $this->create_and_give_item($user->id, 0);

        // Test Level Description.
        $condlvl = new condition((object)['subtype' => 'level', 'levelval' => 5]);
        $desclvl = $condlvl->get_description(true, false, $info);
        $this->assertStringContainsString('5', $desclvl);

        // Test Item Description — one assertion per operator to cover all switch branches.
        $conditem = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 2, 'itemop' => '=']);
        $descitem = $conditem->get_description(true, false, $info);
        $this->assertStringContainsString('2', $descitem);
        $this->assertStringContainsString('Magic Key', $descitem);

        $condgt = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 1, 'itemop' => '>']);
        $this->assertNotEmpty($condgt->get_description(true, false, $info));

        $condlt = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 5, 'itemop' => '<']);
        $this->assertNotEmpty($condlt->get_description(true, false, $info));

        $condgte = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => 1, 'itemop' => '>=']);
        $this->assertNotEmpty($condgte->get_description(true, false, $info));

        // Test Class Description.
        $classid = $this->create_and_assign_class($user->id);
        $condclass = new condition((object)['subtype' => 'class', 'classid' => $classid]);
        $descclass = $condclass->get_description(true, false, $info);
        $this->assertStringContainsString('Warrior', $descclass);
    }

    /**
     * Test that save() serialises all fields correctly regardless of subtype.
     */
    public function test_save(): void {
        $cond = new condition((object)[
            'subtype' => 'item',
            'itemid' => 7,
            'itemqty' => 3,
            'itemop' => '>',
        ]);
        $saved = $cond->save();
        $this->assertSame('item', $saved->subtype);
        $this->assertSame(7, (int)$saved->itemid);
        $this->assertSame(3, (int)$saved->itemqty);
        $this->assertSame('>', $saved->itemop);
        $this->assertSame(0, (int)$saved->levelval);
        $this->assertSame(0, (int)$saved->classid);
    }

    /**
     * Test that get_debug_string() returns a non-empty string.
     */
    public function test_get_debug_string(): void {
        $cond = new condition((object)['subtype' => 'level', 'levelval' => 1]);
        $this->assertNotEmpty($cond->get_debug_string());
    }

    /**
     * Test restriction based on gamification status.
     */
    public function test_is_available_gamification(): void {
        global $DB;

        $cond = new condition((object)['subtype' => 'gamification']);

        // User with gamification enabled.
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));

        // User with gamification explicitly disabled.
        $userdis = $this->getDataGenerator()->create_user();
        $playerdis = new \stdClass();
        $playerdis->blockinstanceid = $this->instanceid;
        $playerdis->userid = $userdis->id;
        $playerdis->currentxp = 0;
        $playerdis->enable_gamification = 0;
        $playerdis->ranking_visibility = 1;
        $playerdis->timecreated = time();
        $playerdis->timemodified = time();
        $DB->insert_record('block_playerhud_user', $playerdis);
        $infodis = new \core_availability\mock_info($this->course, $userdis->id);
        $this->assertFalse($cond->is_available(false, $infodis, true, $userdis->id));

        // User without any player record.
        $usernew = $this->getDataGenerator()->create_user();
        $infonew = new \core_availability\mock_info($this->course, $usernew->id);
        $this->assertFalse($cond->is_available(false, $infonew, true, $usernew->id));
    }

    /**
     * Test that level check works when the block configdata is missing or corrupt.
     *
     * Exercises the fallback at condition.php L98 where unserialize returns false
     * and the code falls back to a bare stdClass so get_game_stats still runs.
     */
    public function test_is_available_level_invalid_configdata(): void {
        global $DB;

        // Create an isolated course with a block that has empty configdata.
        $course2 = $this->getDataGenerator()->create_course();
        $ctx2 = \context_course::instance($course2->id);

        $bi = new \stdClass();
        $bi->blockname = 'playerhud';
        $bi->parentcontextid = $ctx2->id;
        $bi->showinsubcontexts = 0;
        $bi->pagetypepattern = 'course-view-*';
        $bi->defaultregion = 'side-pre';
        $bi->defaultweight = 0;
        $bi->configdata = '';
        $bi->timecreated = time();
        $bi->timemodified = time();
        $instanceid2 = $DB->insert_record('block_instances', $bi);

        $user = $this->getDataGenerator()->create_user();
        $player = new \stdClass();
        $player->blockinstanceid = $instanceid2;
        $player->userid = $user->id;
        $player->currentxp = 500;
        $player->enable_gamification = 1;
        $player->ranking_visibility = 1;
        $player->timecreated = time();
        $player->timemodified = time();
        $DB->insert_record('block_playerhud_user', $player);

        $info = new \core_availability\mock_info($course2, $user->id);
        $cond = new condition((object)['subtype' => 'level', 'levelval' => 1]);

        // Should not throw even with empty configdata; result is a boolean.
        $this->assertIsBool($cond->is_available(false, $info, true, $user->id));
    }

    /**
     * Test that an unknown subtype in is_available falls through to false.
     */
    public function test_is_available_unknown_subtype(): void {
        $user = $this->create_player_with_xp(100);
        $info = new \core_availability\mock_info($this->course, $user->id);

        $cond = new condition((object)['subtype' => 'unknown_subtype']);
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
    }

    /**
     * Test get_description for gamification and unknown/missing subtypes.
     */
    public function test_get_description_other_subtypes(): void {
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);

        // Gamification subtype returns a non-empty string.
        $condgam = new condition((object)['subtype' => 'gamification']);
        $this->assertNotEmpty($condgam->get_description(true, false, $info));

        // Missing subtype returns an empty string.
        $condnone = new condition((object)[]);
        $this->assertSame('', $condnone->get_description(true, false, $info));

        // Unknown subtype falls through to an empty string.
        $condunk = new condition((object)['subtype' => 'unknown']);
        $this->assertSame('', $condunk->get_description(true, false, $info));
    }

    /**
     * Regression test for a stored-XSS fix: levelval must be cast to int before being
     * used, both when persisted (save()) and when rendered (get_description()), so a
     * forged availabilityconditionsjson payload cannot inject HTML/JS.
     */
    public function test_save_and_description_cast_levelval_to_int(): void {
        $payload = '999<img src=x onerror=alert(1)>';
        $cond = new condition((object)['subtype' => 'level', 'levelval' => $payload]);

        $saved = $cond->save();
        $this->assertSame(999, $saved->levelval);

        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);
        $desc = $cond->get_description(true, false, $info);
        $this->assertStringNotContainsString('<img', $desc);
        $this->assertStringContainsString('999', $desc);
    }

    /**
     * Regression test for a stored-XSS fix: itemqty must be cast to int before being
     * used, both when persisted (save()) and when rendered (get_description()).
     */
    public function test_save_and_description_cast_itemqty_to_int(): void {
        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);
        $itemid = $this->create_and_give_item($user->id, 0);

        $payload = '5<img src=x onerror=alert(1)>';
        $cond = new condition((object)['subtype' => 'item', 'itemid' => $itemid, 'itemqty' => $payload, 'itemop' => '>=']);

        $saved = $cond->save();
        $this->assertSame(5, $saved->itemqty);

        $desc = $cond->get_description(true, false, $info);
        $this->assertStringNotContainsString('<img', $desc);
        $this->assertStringContainsString('5', $desc);
    }

    /**
     * Regression test: tampered block configdata containing a serialized object of an
     * arbitrary class must not be instantiated. Proves unserialize_object() (which
     * restricts allowed_classes) is used instead of a bare unserialize().
     */
    public function test_is_available_level_blocks_object_injection_in_configdata(): void {
        global $DB;

        condition_test_gadget::$triggered = false;

        $gadget = new condition_test_gadget();
        $DB->set_field('block_instances', 'configdata', base64_encode(serialize($gadget)), ['id' => $this->instanceid]);

        $user = $this->create_player_with_xp(250);
        $info = new \core_availability\mock_info($this->course, $user->id);
        $cond = new condition((object)['subtype' => 'level', 'levelval' => 1]);

        $this->assertIsBool($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse(condition_test_gadget::$triggered, 'Arbitrary object must not be instantiated from configdata');
    }

    /**
     * Regression test: get_description() must resolve item names only from the item
     * catalog of the course's own PlayerHUD block instance, never from another block.
     */
    public function test_get_description_item_scoped_to_course_block(): void {
        global $DB;

        $othercourse = $this->getDataGenerator()->create_course();
        $otherinstanceid = $this->create_playerhud_block($othercourse);

        $otheritem = new \stdClass();
        $otheritem->blockinstanceid = $otherinstanceid;
        $otheritem->name = 'Secret Blade From Other Course';
        $otheritem->xp = 0;
        $otheritem->timecreated = time();
        $otheritem->timemodified = time();
        $otheritemid = $DB->insert_record('block_playerhud_items', $otheritem);

        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);
        $cond = new condition((object)['subtype' => 'item', 'itemid' => $otheritemid, 'itemqty' => 1, 'itemop' => '>=']);

        $desc = $cond->get_description(true, false, $info);
        $this->assertStringNotContainsString('Secret Blade From Other Course', $desc);
    }

    /**
     * Regression test: get_description() must resolve RPG class names only from the
     * class catalog of the course's own PlayerHUD block instance, never from another.
     */
    public function test_get_description_class_scoped_to_course_block(): void {
        global $DB;

        $othercourse = $this->getDataGenerator()->create_course();
        $otherinstanceid = $this->create_playerhud_block($othercourse);

        $otherclass = new \stdClass();
        $otherclass->blockinstanceid = $otherinstanceid;
        $otherclass->name = 'Secret Class From Other Course';
        $otherclass->base_hp = 100;
        $otherclass->timecreated = time();
        $otherclass->timemodified = time();
        $otherclassid = $DB->insert_record('block_playerhud_classes', $otherclass);

        $user = $this->create_player_with_xp(0);
        $info = new \core_availability\mock_info($this->course, $user->id);
        $cond = new condition((object)['subtype' => 'class', 'classid' => $otherclassid]);

        $desc = $cond->get_description(true, false, $info);
        $this->assertStringNotContainsString('Secret Class From Other Course', $desc);
    }

    /**
     * Regression test: is_available() must count inventory only for items belonging to
     * the course's own PlayerHUD block instance. A user owning an item granted through a
     * different course's block must not satisfy a condition forged to point at it.
     */
    public function test_is_available_item_scoped_to_course_block(): void {
        global $DB;

        $othercourse = $this->getDataGenerator()->create_course();
        $otherinstanceid = $this->create_playerhud_block($othercourse);

        $user = $this->create_player_with_xp(0);

        $otheritem = new \stdClass();
        $otheritem->blockinstanceid = $otherinstanceid;
        $otheritem->name = 'Other Course Item';
        $otheritem->xp = 0;
        $otheritem->timecreated = time();
        $otheritem->timemodified = time();
        $otheritemid = $DB->insert_record('block_playerhud_items', $otheritem);

        $DB->insert_record('block_playerhud_inventory', (object)[
            'userid' => $user->id,
            'itemid' => $otheritemid,
            'dropid' => 0,
            'source' => 'test',
            'timecreated' => time(),
        ]);

        $info = new \core_availability\mock_info($this->course, $user->id);
        $cond = new condition((object)['subtype' => 'item', 'itemid' => $otheritemid, 'itemqty' => 1, 'itemop' => '>=']);

        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
    }

    /**
     * Helper to create a PlayerHUD block instance in a given course.
     *
     * @param \stdClass $course Course object.
     * @return int The new block instance ID.
     */
    protected function create_playerhud_block(\stdClass $course): int {
        global $DB;

        $ctx = \context_course::instance($course->id);

        $bi = new \stdClass();
        $bi->blockname = 'playerhud';
        $bi->parentcontextid = $ctx->id;
        $bi->showinsubcontexts = 0;
        $bi->pagetypepattern = 'course-view-*';
        $bi->defaultregion = 'side-pre';
        $bi->defaultweight = 0;
        $bi->configdata = base64_encode(serialize(new \stdClass()));
        $bi->timecreated = time();
        $bi->timemodified = time();

        return $DB->insert_record('block_instances', $bi);
    }
}
