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
 * Condition class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_playerhud;

/**
 * Condition class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
            'levelval' => $this->config->levelval ?? 0,
            'itemid' => $this->config->itemid ?? 0,
            'itemqty' => $this->config->itemqty ?? 1,
            'itemop' => $this->config->itemop ?? '>=',
        ];
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

        $courseid = $info->get_course()->id;
        $context = \context_course::instance($courseid);

        $sql = "SELECT bi.id, bi.configdata
                  FROM {block_instances} bi
                 WHERE bi.blockname = 'playerhud'
                   AND bi.parentcontextid = :ctxid";

        $block = $DB->get_record_sql($sql, ['ctxid' => $context->id], IGNORE_MULTIPLE);

        if (!$block) {
            return false;
        }

        $player = $DB->get_record('block_playerhud_user', [
            'blockinstanceid' => $block->id,
            'userid' => $userid,
        ]);

        $currentxp = $player ? $player->currentxp : 0;

        // Level Logic (Greater than or Equal).
        if (isset($this->config->subtype) && $this->config->subtype === 'level') {
            $blockconfig = unserialize(base64_decode($block->configdata));
            if (!$blockconfig) {
                $blockconfig = new \stdClass();
            }

            if (class_exists('\block_playerhud\game')) {
                $stats = \block_playerhud\game::get_game_stats($blockconfig, $block->id, $currentxp);
                return ($stats['level'] >= (int)$this->config->levelval);
            }
            return false;
        }

        // Item Logic with Operators.
        if (isset($this->config->subtype) && $this->config->subtype === 'item') {
            $count = $DB->count_records('block_playerhud_inventory', [
                'userid' => $userid,
                'itemid' => (int)$this->config->itemid,
            ]);

            $qty = (int)$this->config->itemqty;
            $op = $this->config->itemop ?? '>=';

            switch ($op) {
                case '>':
                    return ($count > $qty);
                case '<':
                    return ($count < $qty);
                case '=':
                    return ($count == $qty);
                default:
                    return ($count >= $qty);
            }
        }

        return false;
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
            return get_string('requires_level', 'availability_playerhud', $this->config->levelval);
        }

        if ($this->config->subtype === 'item') {
            $itemname = get_string('missing', 'availability_playerhud');
            if (!empty($this->config->itemid)) {
                $itemname = $DB->get_field('block_playerhud_items', 'name', ['id' => $this->config->itemid]) ?: $itemname;
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
            $a->qty = $this->config->itemqty;
            $a->item = format_string($itemname);
            $a->op = $optext;

            return get_string('requires_item', 'availability_playerhud', $a);
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
}
