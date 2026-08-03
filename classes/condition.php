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
 * Condition class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud;

/**
 * Condition class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var \stdClass Structure of the data saved in DB. */
    protected $config;

    /**
     * Constructor.
     * @param \stdClass $structure Data from availability tree.
     */
    public function __construct($structure) {
        $this->config = $structure;
    }

    /**
     * Saves data to the database.
     * @return \stdClass
     */
    public function save() {
        return (object)[
            'subtype' => $this->config->subtype,
            'levelval' => (int)($this->config->levelval ?? 0),
            'itemid' => (int)($this->config->itemid ?? 0),
            'itemqty' => (int)($this->config->itemqty ?? 1),
            'itemop' => $this->config->itemop ?? '>=',
            'classid' => (int)($this->config->classid ?? 0),
        ];
    }

    /**
     * Fetches the PlayerHUD block instance attached to a course, if any.
     *
     * @param int $courseid Course ID.
     * @return \stdClass|false Block instance record (id, configdata) or false if none exists.
     */
    private function get_block_instance(int $courseid) {
        global $DB;

        $context = \context_course::instance($courseid);

        $sql = "SELECT bi.id, bi.configdata
                  FROM {block_instances} bi
                 WHERE bi.blockname = 'playerhud'
                   AND bi.parentcontextid = :ctxid";

        return $DB->get_record_sql($sql, ['ctxid' => $context->id], IGNORE_MULTIPLE);
    }

    /**
     * Checks availability.
     *
     * @param bool $not If true, this is a "must not" condition.
     * @param \core_availability\info $info Info about context.
     * @param bool $grabthelot If true, retrieve all data.
     * @param int $userid User ID.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $block = $this->get_block_instance($info->get_course()->id);
        $allow = false;

        if ($block) {
            $player = $DB->get_record('block_playerhud_user', [
                'blockinstanceid' => $block->id,
                'userid' => $userid,
            ]);

            $currentxp = $player ? $player->currentxp : 0;

            // Level Logic (Greater than or Equal).
            if (isset($this->config->subtype) && $this->config->subtype === 'level') {
                // Unserialize_object() restricts allowed_classes, unlike a bare unserialize(),
                // preventing instantiation of arbitrary objects from a tampered configdata.
                $blockconfig = unserialize_object(base64_decode($block->configdata));

                if (class_exists('\block_playerhud\game')) {
                    $stats = \block_playerhud\game::get_game_stats($blockconfig, $block->id, $currentxp);
                    $allow = ($stats['level'] >= (int)$this->config->levelval);
                }
            } else if (isset($this->config->subtype) && $this->config->subtype === 'item') {
                // Item Logic with Operators.
                $sql = "SELECT COUNT(inv.id)
                          FROM {block_playerhud_inventory} inv
                          JOIN {block_playerhud_items} i ON i.id = inv.itemid
                         WHERE inv.userid        = :userid
                           AND inv.itemid        = :itemid
                           AND i.blockinstanceid = :blockinstanceid";

                $count = $DB->count_records_sql($sql, [
                    'userid'          => $userid,
                    'itemid'          => (int)$this->config->itemid,
                    'blockinstanceid' => $block->id,
                ]);

                $qty = (int)$this->config->itemqty;
                $op = $this->config->itemop ?? '>=';

                switch ($op) {
                    case '>':
                        $allow = ($count > $qty);
                        break;
                    case '<':
                        $allow = ($count < $qty);
                        break;
                    case '=':
                        $allow = ($count == $qty);
                        break;
                    default:
                        $allow = ($count >= $qty);
                        break;
                }
            } else if (isset($this->config->subtype) && $this->config->subtype === 'gamification') {
                // Gamification Logic: user must have gamification enabled.
                $allow = ($player && $player->enable_gamification == 1);
            } else if (isset($this->config->subtype) && $this->config->subtype === 'class') {
                // Class Logic: user must have the RPG class assigned.
                $classid = (int)($this->config->classid ?? 0);

                if ($classid > 0) {
                    $sql = "SELECT rp.id
                              FROM {block_playerhud_rpg_progress} rp
                              JOIN {block_playerhud_classes} c ON c.id = rp.classid
                             WHERE rp.userid        = :userid
                               AND rp.classid       = :classid
                               AND c.blockinstanceid = :blockinstanceid";

                    $allow = $DB->record_exists_sql($sql, [
                        'userid'          => $userid,
                        'classid'         => $classid,
                        'blockinstanceid' => $block->id,
                    ]);
                }
            }
        }

        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Description displayed to student.
     *
     * @param bool $full Full description.
     * @param bool $not NOT logic.
     * @param \core_availability\info $info Context info.
     * @return string Description string.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if (!isset($this->config->subtype)) {
            return '';
        }

        if ($this->config->subtype === 'level') {
            $string = $not ? 'requires_not_level' : 'requires_level';
            return get_string($string, 'availability_playerhud', (int)$this->config->levelval);
        }

        $context = $info->get_context();
        $block = $this->get_block_instance($info->get_course()->id);

        if ($this->config->subtype === 'item') {
            $itemname = get_string('missing', 'availability_playerhud');
            if (!empty($this->config->itemid) && $block) {
                $itemname = $DB->get_field('block_playerhud_items', 'name', [
                    'id' => (int)$this->config->itemid,
                    'blockinstanceid' => $block->id,
                ]) ?: $itemname;
            }

            $op = $this->config->itemop ?? '>=';
            $optext = '';

            // Map operators to language strings.
            switch ($op) {
                case '>':
                    $optext = get_string('op_text_more', 'availability_playerhud');
                    break;
                case '<':
                    $optext = get_string('op_text_less', 'availability_playerhud');
                    break;
                case '=':
                    $optext = get_string('op_text_equal', 'availability_playerhud');
                    break;
                case '>=':
                default:
                    $optext = get_string('op_text_atleast', 'availability_playerhud');
                    break;
            }

            $a = new \stdClass();
            $a->qty = (int)$this->config->itemqty;
            $a->item = format_string($itemname, true, ['context' => $context]);
            $a->op = $optext;

            $string = $not ? 'requires_not_item' : 'requires_item';
            return get_string($string, 'availability_playerhud', $a);
        }

        if ($this->config->subtype === 'gamification') {
            $string = $not ? 'requires_not_gamification_active' : 'requires_gamification_active';
            return get_string($string, 'availability_playerhud');
        }

        if ($this->config->subtype === 'class') {
            $classname = get_string('missing', 'availability_playerhud');
            if (!empty($this->config->classid) && $block) {
                $classname = $DB->get_field(
                    'block_playerhud_classes',
                    'name',
                    ['id' => (int)$this->config->classid, 'blockinstanceid' => $block->id]
                ) ?: $classname;
            }

            $string = $not ? 'requires_not_class' : 'requires_class';
            return get_string($string, 'availability_playerhud', format_string($classname, true, ['context' => $context]));
        }

        return '';
    }

    /**
     * Debug string.
     * @return string
     */
    public function get_debug_string() {
        return 'PlayerHUD Restriction';
    }

    /**
     * Remaps itemid/classid to the restored course's own PlayerHUD item/class after a
     * course backup and restore, mirroring the pattern used by availability_grade for
     * grade_item. Without this, the ids keep pointing at the source course's rows.
     *
     * @param string $restoreid Restore ID.
     * @param int $courseid ID of target course.
     * @param \base_logger $logger Logger for any warnings.
     * @param string $name Name of this item (for use in warning messages).
     * @return bool True if there was any change.
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        $changed = false;

        if (!empty($this->config->itemid)) {
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'playerhud_item', $this->config->itemid);
            if ($rec && $rec->newitemid) {
                $this->config->itemid = (int)$rec->newitemid;
            } else {
                $this->config->itemid = 0;
                $logger->process(
                    'Restored item (' . $name . ') has an availability condition on a PlayerHUD item ' .
                        'that was not restored',
                    \backup::LOG_WARNING
                );
            }
            $changed = true;
        }

        if (!empty($this->config->classid)) {
            $rec = \restore_dbops::get_backup_ids_record($restoreid, 'playerhud_class', $this->config->classid);
            if ($rec && $rec->newitemid) {
                $this->config->classid = (int)$rec->newitemid;
            } else {
                $this->config->classid = 0;
                $logger->process(
                    'Restored item (' . $name . ') has an availability condition on a PlayerHUD ' .
                        'character class that was not restored',
                    \backup::LOG_WARNING
                );
            }
            $changed = true;
        }

        return $changed;
    }
}
