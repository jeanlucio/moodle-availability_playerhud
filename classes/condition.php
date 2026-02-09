<?php
namespace availability_playerhud;

defined('MOODLE_INTERNAL') || die();

class condition extends \core_availability\condition {

    /** @var array Estrutura dos dados salvos no BD */
    protected $config;

    /**
     * Constructor.
     * @param \stdClass $structure Dados vindos da árvore de disponibilidade (JSON)
     */
    public function __construct($structure) {
        $this->config = $structure;
    }

    /**
     * Salva os dados no banco.
     */
    public function save() {
        return (object)[
            'type' => 'playerhud',
            'subtype' => $this->config->subtype, // 'level' ou 'item'
            'levelval' => $this->config->levelval ?? 0,
            'itemid' => $this->config->itemid ?? 0,
            'itemqty' => $this->config->itemqty ?? 1
        ];
    }

    /**
     * Verifica se está disponível para o usuário.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $courseid = $info->get_course()->id;
        
        // 1. Encontra o Bloco no curso
        $sql = "SELECT bi.id, bi.configdata 
                  FROM {block_instances} bi 
                 WHERE bi.blockname = 'playerhud' 
                   AND bi.parentcontextid = :ctxid";
        
        $context = \context_course::instance($courseid);
        $block = $DB->get_record_sql($sql, ['ctxid' => $context->id], IGNORE_MULTIPLE);

        if (!$block) {
            return false; // Bloqueia se não houver jogo configurado
        }

        // 2. Busca dados do Jogador
        $player = $DB->get_record('block_playerhud_user', [
            'blockinstanceid' => $block->id,
            'userid' => $userid
        ]);

        $currentxp = $player ? $player->currentxp : 0;

        // 3. Verifica RESTRIÇÃO POR NÍVEL
        if ($this->config->subtype === 'level') {
            $blockconfig = unserialize(base64_decode($block->configdata));
            if (!$blockconfig) $blockconfig = new \stdClass();

            // Reutiliza a lógica central do jogo para calcular nível
            // É vital que a classe game.php do bloco esteja acessível
            if (class_exists('\block_playerhud\game')) {
                $stats = \block_playerhud\game::get_game_stats($blockconfig, $block->id, $currentxp);
                return ($stats['level'] >= (int)$this->config->levelval);
            }
            return false;
        }

        // 4. Verifica RESTRIÇÃO POR ITEM
        if ($this->config->subtype === 'item') {
            $itemid = (int)$this->config->itemid;
            $req_qty = (int)$this->config->itemqty;

            // Conta quantos itens desse tipo o aluno tem no inventário
            $count = $DB->count_records('block_playerhud_inventory', [
                'userid' => $userid,
                'itemid' => $itemid
            ]);

            return ($count >= $req_qty);
        }

        return false;
    }

    /**
     * Mensagem exibida para o aluno quando bloqueado.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if ($this->config->subtype === 'level') {
            return get_string('requires_level', 'availability_playerhud', $this->config->levelval);
        }

        if ($this->config->subtype === 'item') {
            $itemname = 'Item desconhecido';
            // Tenta buscar nome do item (cache leve)
            if (!empty($this->config->itemid)) {
                $itemname = $DB->get_field('block_playerhud_items', 'name', ['id' => $this->config->itemid]) ?: 'Item removido';
            }
            
            $a = new \stdClass();
            $a->qty = $this->config->itemqty;
            $a->item = format_string($itemname);
            
            return get_string('requires_item', 'availability_playerhud', $a);
        }

        return '';
    }

    /**
     * Usado pelo Moodle para mostrar opções no Javascript (Dropdown de itens).
     */
    public static function get_js_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;
        
        // Busca itens do curso para popular o select do formulário
        $context = \context_course::instance($course->id);
        $block = $DB->get_record_sql("SELECT bi.id FROM {block_instances} bi 
                                       WHERE bi.blockname = 'playerhud' 
                                         AND bi.parentcontextid = :ctxid", 
                                     ['ctxid' => $context->id], IGNORE_MULTIPLE);
        
        $items = [];
        if ($block) {
            $records = $DB->get_records('block_playerhud_items', ['blockinstanceid' => $block->id], 'name ASC', 'id, name');
            foreach ($records as $r) {
                $items[] = ['id' => $r->id, 'name' => format_string($r->name)];
            }
        }

        return [
            'items' => $items
        ];
    }
    
    public function get_debug_string() {
        return 'PlayerHUD Restriction';
    }
}
