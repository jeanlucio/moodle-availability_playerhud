<?php
namespace availability_playerhud;

defined('MOODLE_INTERNAL') || die();

/**
 * Frontend class for PlayerHUD availability.
 * Handles the UI aspects: showing the plugin in the list and passing data to JS.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Decides whether this plugin should be available in a given course.
     * Only allows adding this restriction if the PlayerHUD block exists in the course.
     *
     * @param \stdClass $course Course object
     * @param \cm_info|null $cm Course-module currently being edited
     * @param \section_info|null $section Section currently being edited
     * @return bool True if the restriction can be added
     */
    protected function allow_add($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;
        
        $context = \context_course::instance($course->id);
        
        // Verifica se existe uma instância do bloco neste curso
        return $DB->record_exists('block_instances', [
            'blockname' => 'playerhud',
            'parentcontextid' => $context->id
        ]);
    }

    /**
     * Pass data to the Javascript form (initInner).
     *
     * @param \stdClass $course
     * @param \cm_info|null $cm
     * @param \section_info|null $section
     * @return array Parameters passed to JS initInner
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;
        
        $context = \context_course::instance($course->id);
        
        // Busca o bloco para pegar os itens
        $block = $DB->get_record('block_instances', [
            'blockname' => 'playerhud', 
            'parentcontextid' => $context->id
        ], 'id', IGNORE_MULTIPLE);
        
        $items = [];
        if ($block) {
            $records = $DB->get_records('block_playerhud_items', ['blockinstanceid' => $block->id], 'name ASC', 'id, name');
            foreach ($records as $r) {
                $items[] = [
                    'id' => (int)$r->id, 
                    'name' => format_string($r->name)
                ];
            }
        }

        // Retorna um array. O primeiro elemento será o argumento 'items' no JS.
        return [$items];
    }

    /**
     * Define strings required by Javascript.
     *
     * @return array List of string identifiers
     */
    protected function get_javascript_strings() {
        return [
            'empty', 
            'option_level', 
            'option_item',
            'label_type', 
            'label_min_level', 
            'label_item_select', 
            'label_item_qty'
        ];
    }
}
