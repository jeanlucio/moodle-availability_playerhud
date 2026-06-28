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
 * Behat step definitions for the PlayerHUD availability condition.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Behat step definition files must not declare a namespace.

/**
 * Step definitions for availability_playerhud Behat tests.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_availability_playerhud extends behat_base {
    /**
     * Creates a PlayerHUD block in the given course with a deterministic XP-per-level config.
     * Any pre-existing PlayerHUD block in the course is removed first to keep tests idempotent.
     *
     * @Given /^a PlayerHUD block exists in course "([^"]*)" with (\d+) XP per level$/
     * @param string $shortname Course shortname.
     * @param int $xpperlevel XP required to advance one level.
     */
    public function a_playerhud_block_exists_in_course_with_xp_per_level(string $shortname, int $xpperlevel): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $DB->delete_records('block_instances', [
            'blockname' => 'playerhud',
            'parentcontextid' => $context->id,
        ]);

        $config = new stdClass();
        $config->xp_per_level = $xpperlevel;

        $bi = new stdClass();
        $bi->blockname = 'playerhud';
        $bi->parentcontextid = $context->id;
        $bi->showinsubcontexts = 0;
        $bi->pagetypepattern = 'course-view-*';
        $bi->defaultregion = 'side-pre';
        $bi->defaultweight = 0;
        $bi->configdata = base64_encode(serialize($config));
        $bi->timecreated = time();
        $bi->timemodified = time();

        $DB->insert_record('block_instances', $bi);
    }

    /**
     * Creates or updates a PlayerHUD player record for a user in a course, assigning the given XP.
     * Gamification is enabled for this player. Requires the PlayerHUD block to already exist in the course.
     *
     * @Given /^a PlayerHUD player "([^"]*)" exists in course "([^"]*)" with (\d+) XP$/
     * @param string $username Username of the Moodle user.
     * @param string $shortname Course shortname.
     * @param int $xp XP to assign to the player.
     */
    public function a_playerhud_player_exists_in_course_with_xp(string $username, string $shortname, int $xp): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $block = $DB->get_record_sql(
            "SELECT id FROM {block_instances}
              WHERE blockname = 'playerhud'
                AND parentcontextid = :ctxid",
            ['ctxid' => $context->id],
            MUST_EXIST
        );

        $existing = $DB->get_record('block_playerhud_user', [
            'blockinstanceid' => $block->id,
            'userid' => $user->id,
        ]);

        if ($existing) {
            $existing->currentxp = $xp;
            $existing->enable_gamification = 1;
            $existing->timemodified = time();
            $DB->update_record('block_playerhud_user', $existing);
        } else {
            $DB->insert_record('block_playerhud_user', (object)[
                'blockinstanceid' => $block->id,
                'userid' => $user->id,
                'currentxp' => $xp,
                'enable_gamification' => 1,
                'ranking_visibility' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }
    }

    /**
     * Creates or updates a PlayerHUD player record for a user with gamification explicitly disabled.
     * Requires the PlayerHUD block to already exist in the course.
     *
     * @Given /^a PlayerHUD player "([^"]*)" exists in course "([^"]*)" with gamification disabled$/
     * @param string $username Username of the Moodle user.
     * @param string $shortname Course shortname.
     */
    public function a_playerhud_player_exists_with_gamification_disabled(string $username, string $shortname): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $block = $DB->get_record_sql(
            "SELECT id FROM {block_instances}
              WHERE blockname = 'playerhud'
                AND parentcontextid = :ctxid",
            ['ctxid' => $context->id],
            MUST_EXIST
        );

        $existing = $DB->get_record('block_playerhud_user', [
            'blockinstanceid' => $block->id,
            'userid' => $user->id,
        ]);

        if ($existing) {
            $existing->enable_gamification = 0;
            $existing->timemodified = time();
            $DB->update_record('block_playerhud_user', $existing);
        } else {
            $DB->insert_record('block_playerhud_user', (object)[
                'blockinstanceid' => $block->id,
                'userid' => $user->id,
                'currentxp' => 0,
                'enable_gamification' => 0,
                'ranking_visibility' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }
    }
}
