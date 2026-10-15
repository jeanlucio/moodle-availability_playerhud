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
     *
     * The tree re-saves every condition whenever one of them changes (e.g. after a restore remaps an
     * id), and a structure without 'type' is then read back as a malformed subtree, so it must be here.
     *
     * @return \stdClass
     */
    public function save() {
        return (object)[
            'type' => 'playerhud',
            'subtype' => $this->config->subtype,
            'levelval' => (int)($this->config->levelval ?? 0),
            'itemid' => (int)($this->config->itemid ?? 0),
            'itemqty' => (int)($this->config->itemqty ?? 1),
            'itemop' => $this->config->itemop ?? '>=',
            'classid' => (int)($this->config->classid ?? 0),
        ];
    }

    /**
     * Request-scoped cache for one kind of PlayerHUD data.
     *
     * Request scope only: the data is read again on the next page, so nothing has to be invalidated
     * when a student collects, trades or toggles gamification.
     *
     * @param string $area Cache area.
     * @return \cache
     */
    private static function request_cache(string $area) {
        return \cache::make_from_params(\cache_store::MODE_REQUEST, 'availability_playerhud', $area);
    }

    /**
     * Fetches the PlayerHUD block instance attached to a course, if any.
     *
     * @param int $courseid Course ID.
     * @return \stdClass|false Block instance record (id, configdata) or false if none exists.
     */
    private function get_block_instance(int $courseid) {
        global $DB;

        $cache = self::request_cache('blocks');
        $cached = $cache->get($courseid);
        if ($cached !== false) {
            return $cached['block'];
        }
        $context = \context_course::instance($courseid);
        $sql = "SELECT bi.id, bi.configdata
                  FROM {block_instances} bi
                 WHERE bi.blockname = 'playerhud'
                   AND bi.parentcontextid = :ctxid";
        $block = $DB->get_record_sql($sql, ['ctxid' => $context->id], IGNORE_MULTIPLE);
        $cache->set($courseid, ['block' => $block]);
        return $block;
    }

    /**
     * The player record of a user in a block, false if the user never opened it.
     *
     * A missing record is cached too: the core computes each activity's availability once per
     * request, so a record the block creates later in the same request would not change it.
     *
     * @param int $blockid Block instance ID.
     * @param int $userid User ID.
     * @return \stdClass|false
     */
    private static function get_player(int $blockid, int $userid) {
        $cache = self::request_cache('players');
        $key = $blockid . '_' . $userid;
        $cached = $cache->get($key);
        if ($cached === false) {
            $cached = ['player' => \block_playerhud\game::peek_player($blockid, $userid)];
            $cache->set($key, $cached);
        }
        return $cached['player'];
    }

    /**
     * The character class a user picked in a block, 0 if none.
     *
     * Cached like the player, so a course full of class restrictions reads it once.
     *
     * @param int $blockid Block instance ID.
     * @param int $userid User ID.
     * @return int Class ID.
     */
    private static function get_player_class_id(int $blockid, int $userid): int {
        $cache = self::request_cache('classes');
        $key = $blockid . '_' . $userid;
        $classid = $cache->get($key);
        if ($classid === false) {
            $progress = \block_playerhud\game::get_player_class($blockid, $userid);
            $classid = $progress ? (int) $progress->classid : 0;
            $cache->set($key, $classid);
        }
        return (int) $classid;
    }

    /**
     * Names of every item or character class of a block, keyed by id.
     *
     * @param int $blockid Block instance ID.
     * @param string $kind 'items' or 'classes'.
     * @return array
     */
    private static function get_names(int $blockid, string $kind): array {
        $cache = self::request_cache('names');
        $key = $kind . '_' . $blockid;
        $names = $cache->get($key);
        if ($names === false) {
            $names = $kind === 'classes'
                ? \block_playerhud\game::get_class_names($blockid)
                : \block_playerhud\local\external_items::get_names($blockid);
            $cache->set($key, $names);
        }
        return $names;
    }

    /**
     * How many units of an item a user holds.
     *
     * When the core checks a whole course for one user ($grabthelot), every item of the block is
     * loaded at once and reused by the other conditions of that check; a single check reads only
     * its own item.
     *
     * @param int $blockid Block instance ID.
     * @param int $itemid Item ID.
     * @param int $userid User ID.
     * @param bool $grabthelot Whether the core is checking many conditions for this user.
     * @return int
     */
    private static function get_quantity(int $blockid, int $itemid, int $userid, bool $grabthelot): int {
        $cache = self::request_cache('quantities');
        $key = $blockid . '_' . $userid;
        $quantities = $cache->get($key);
        if ($quantities === false && $grabthelot) {
            $quantities = \block_playerhud\local\external_items::get_available_quantities_bulk(
                $blockid,
                array_keys(self::get_names($blockid, 'items')),
                $userid
            );
            $cache->set($key, $quantities);
        }
        if ($quantities !== false) {
            return (int) ($quantities[$itemid] ?? 0);
        }
        return \block_playerhud\local\external_items::get_available_quantity($blockid, $itemid, $userid);
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
        $block = $this->get_block_instance($info->get_course()->id);
        $allow = false;
        $subtype = $this->config->subtype ?? '';

        if ($block) {
            $blockid = (int) $block->id;
            $player = self::get_player($blockid, (int) $userid);

            if ($subtype === 'level') {
                // Unserialize_object() restricts allowed_classes, unlike a bare unserialize(),
                // preventing instantiation of arbitrary objects from a tampered configdata.
                $blockconfig = unserialize_object(base64_decode((string) $block->configdata));
                if (!$blockconfig instanceof \stdClass) {
                    $blockconfig = new \stdClass();
                }
                $level = \block_playerhud\game::level_for_xp($blockconfig, $player ? (int) $player->currentxp : 0);
                $allow = ($level >= (int) $this->config->levelval);
            } else if ($subtype === 'item') {
                $count = self::get_quantity($blockid, (int) $this->config->itemid, (int) $userid, (bool) $grabthelot);
                $qty = (int) $this->config->itemqty;

                switch ($this->config->itemop ?? '>=') {
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
            } else if ($subtype === 'gamification') {
                $allow = ($player && $player->enable_gamification == 1);
            } else if ($subtype === 'class') {
                $classid = (int) ($this->config->classid ?? 0);
                if ($classid > 0 && isset(self::get_names($blockid, 'classes')[$classid])) {
                    $allow = (self::get_player_class_id($blockid, (int) $userid) === $classid);
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
     * Item and class names go through description_format_string(), so the core formats them once
     * the page is ready rather than while the course cache is being built.
     *
     * @param bool $full Full description.
     * @param bool $not NOT logic.
     * @param \core_availability\info $info Context info.
     * @return string Description string.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        if (!isset($this->config->subtype)) {
            return '';
        }

        if ($this->config->subtype === 'level') {
            $string = $not ? 'requires_not_level' : 'requires_level';
            return get_string($string, 'availability_playerhud', (int)$this->config->levelval);
        }

        if ($this->config->subtype === 'gamification') {
            $string = $not ? 'requires_not_gamification_active' : 'requires_gamification_active';
            return get_string($string, 'availability_playerhud');
        }

        $block = $this->get_block_instance($info->get_course()->id);
        $missing = get_string('missing', 'availability_playerhud');

        if ($this->config->subtype === 'item') {
            $names = $block ? self::get_names((int) $block->id, 'items') : [];
            $itemid = (int) ($this->config->itemid ?? 0);
            $itemname = isset($names[$itemid]) ? self::description_format_string($names[$itemid]) : $missing;

            switch ($this->config->itemop ?? '>=') {
                case '>':
                    $optext = get_string('op_text_more', 'availability_playerhud');
                    break;
                case '<':
                    $optext = get_string('op_text_less', 'availability_playerhud');
                    break;
                case '=':
                    $optext = get_string('op_text_equal', 'availability_playerhud');
                    break;
                default:
                    $optext = get_string('op_text_atleast', 'availability_playerhud');
                    break;
            }

            $a = new \stdClass();
            $a->qty = (int)$this->config->itemqty;
            $a->item = $itemname;
            $a->op = $optext;

            $string = $not ? 'requires_not_item' : 'requires_item';
            return get_string($string, 'availability_playerhud', $a);
        }

        if ($this->config->subtype === 'class') {
            $names = $block ? self::get_names((int) $block->id, 'classes') : [];
            $classid = (int) ($this->config->classid ?? 0);
            $classname = isset($names[$classid]) ? self::description_format_string($names[$classid]) : $missing;

            $string = $not ? 'requires_not_class' : 'requires_class';
            return get_string($string, 'availability_playerhud', $classname);
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
        $refs = [
            'itemid' => ['playerhud_item', 'items', 'item'],
            'classid' => ['playerhud_class', 'classes', 'character class'],
        ];
        foreach ($refs as $field => [$mapping, $kind, $label]) {
            if (empty($this->config->$field)) {
                continue;
            }
            $rec = \restore_dbops::get_backup_ids_record($restoreid, $mapping, $this->config->$field);
            if ($rec && $rec->newitemid) {
                $this->config->$field = (int) $rec->newitemid;
                $changed = true;
            } else if (!self::belongs_to_course($kind, (int) $this->config->$field, (int) $courseid)) {
                $this->config->$field = 0;
                $logger->process(
                    'Restored item (' . $name . ') has an availability condition on a PlayerHUD ' . $label .
                        ' that was not restored',
                    \backup::LOG_WARNING
                );
                $changed = true;
            }
        }
        return $changed;
    }

    /**
     * Whether a PlayerHUD item or class belongs to the block of a course.
     *
     * Without a restore mapping, an id that already belongs to the target course comes from the same
     * course (a duplicated activity or section) and stays valid. Read uncached: during a restore the
     * target block may have been created in this same request.
     *
     * @param string $kind 'items' or 'classes'.
     * @param int $id Item or class ID.
     * @param int $courseid Target course ID.
     * @return bool
     */
    private static function belongs_to_course(string $kind, int $id, int $courseid): bool {
        global $DB;
        $blockid = (int) $DB->get_field('block_instances', 'id', [
            'blockname' => 'playerhud',
            'parentcontextid' => \context_course::instance($courseid)->id,
        ], IGNORE_MULTIPLE);
        if (!$blockid) {
            return false;
        }
        if ($kind === 'classes') {
            return isset(\block_playerhud\game::get_class_names($blockid)[$id]);
        }
        return \block_playerhud\local\external_items::belongs_to_instance($id, $blockid);
    }
}
