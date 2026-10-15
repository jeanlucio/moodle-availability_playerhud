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
 * Repairs PlayerHUD restrictions saved without their condition type.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud\local;

/**
 * Restores the missing "type" of PlayerHUD conditions in stored availability trees.
 *
 * Until v1.5.1, condition::save() left out "type". Any re-save of a tree holding a PlayerHUD
 * condition (a restore remapping an id, a duplicated activity) therefore wrote a leaf the core
 * reads back as a malformed subtree, locking or unlocking content regardless of the student.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_repair {
    /** @var string[] Subtypes this plugin writes. */
    private const SUBTYPES = ['level', 'item', 'gamification', 'class'];

    /** @var string[] Fields condition::save() always writes besides "type" and "subtype". */
    private const FIELDS = ['levelval', 'itemid', 'itemqty', 'itemop', 'classid'];

    /**
     * Repairs every course module and section availability tree with an untyped PlayerHUD leaf.
     *
     * Only leaves carrying the exact set of fields this plugin writes are touched, so trees of
     * other plugins and already valid trees are never changed; running it twice is harmless.
     *
     * @return int Number of trees repaired.
     */
    public static function repair_all(): int {
        global $DB;

        $repaired = 0;
        $courseids = [];
        foreach (['course_modules', 'course_sections'] as $table) {
            $rs = $DB->get_recordset_select($table, 'availability IS NOT NULL', null, 'id', 'id, course, availability');
            foreach ($rs as $record) {
                if (strpos($record->availability, '"subtype"') === false) {
                    continue;
                }
                $tree = json_decode($record->availability);
                if (!is_object($tree) || !self::repair_node($tree)) {
                    continue;
                }
                $DB->set_field($table, 'availability', json_encode($tree), ['id' => $record->id]);
                $courseids[$record->course] = true;
                $repaired++;
            }
            $rs->close();
        }
        foreach (array_keys($courseids) as $courseid) {
            rebuild_course_cache($courseid, true);
        }
        return $repaired;
    }

    /**
     * Adds the missing type to PlayerHUD leaves under a node.
     *
     * @param \stdClass $node Availability tree or condition node, changed in place.
     * @return bool True if anything under the node changed.
     */
    private static function repair_node(\stdClass $node): bool {
        if (isset($node->c) && is_array($node->c)) {
            $changed = false;
            foreach ($node->c as $child) {
                if (is_object($child) && self::repair_node($child)) {
                    $changed = true;
                }
            }
            return $changed;
        }
        if (isset($node->type) || isset($node->op) || !self::is_untyped_playerhud_leaf($node)) {
            return false;
        }
        $node->type = 'playerhud';
        return true;
    }

    /**
     * Whether a node is a PlayerHUD condition saved without its type.
     *
     * @param \stdClass $node Node without "type" and "op".
     * @return bool
     */
    private static function is_untyped_playerhud_leaf(\stdClass $node): bool {
        if (!isset($node->subtype) || !in_array($node->subtype, self::SUBTYPES, true)) {
            return false;
        }
        foreach (self::FIELDS as $field) {
            if (!property_exists($node, $field)) {
                return false;
            }
        }
        return true;
    }
}
