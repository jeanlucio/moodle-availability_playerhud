<?php
namespace availability_playerhud;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition class for PlayerHUD availability.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var \stdClass Structure of the data saved in DB */
    protected $config;

    /**
     * Constructor.
     * @param \stdClass $structure Data from availability tree
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
            'itemqty' => $this->config->itemqty ?? 1
        ];
    }

    /**
     * Checks availability.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $courseid = $info->get_course()->id;
        $context = \context_course::instance($courseid);

        $block = $DB->get_record_sql("SELECT bi.id, bi.configdata 
                                        FROM {block_instances} bi 
                                       WHERE bi.blockname = 'playerhud' 
                                         AND bi.parentcontextid = :ctxid", 
                                     ['ctxid' => $context->id], IGNORE_MULTIPLE);

        if (!$block) {
            return false;
        }

        $player = $DB->get_record('block_playerhud_user', [
            'blockinstanceid' => $block->id,
            'userid' => $userid
        ]);

        $currentxp = $player ? $player->currentxp : 0;

        // Level Logic
        if (isset($this->config->subtype) && $this->config->subtype === 'level') {
            $blockconfig = unserialize(base64_decode($block->configdata));
            if (!$blockconfig) $blockconfig = new \stdClass();

            if (class_exists('\block_playerhud\game')) {
                $stats = \block_playerhud\game::get_game_stats($blockconfig, $block->id, $currentxp);
                return ($stats['level'] >= (int)$this->config->levelval);
            }
            return false;
        }

        // Item Logic
        if (isset($this->config->subtype) && $this->config->subtype === 'item') {
            $count = $DB->count_records('block_playerhud_inventory', [
                'userid' => $userid,
                'itemid' => (int)$this->config->itemid
            ]);
            return ($count >= (int)$this->config->itemqty);
        }

        return false;
    }

    /**
     * Description displayed to student.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if (!isset($this->config->subtype)) return '';

        if ($this->config->subtype === 'level') {
            return get_string('requires_level', 'availability_playerhud', $this->config->levelval);
        }

        if ($this->config->subtype === 'item') {
            $itemname = get_string('missing', 'availability_playerhud');
            if (!empty($this->config->itemid)) {
                $itemname = $DB->get_field('block_playerhud_items', 'name', ['id' => $this->config->itemid]) ?: $itemname;
            }
            $a = new \stdClass();
            $a->qty = $this->config->itemqty;
            $a->item = format_string($itemname);
            return get_string('requires_item', 'availability_playerhud', $a);
        }
        return '';
    }

    /**
     * Debug string.
     */
    public function get_debug_string() {
        return 'PlayerHUD Restriction';
    }
}
