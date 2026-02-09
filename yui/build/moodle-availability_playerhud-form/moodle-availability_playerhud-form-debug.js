YUI.add('moodle-availability_playerhud-form', function (Y, NAME) {

YUI.add('moodle-availability_playerhud-form', function(Y) {
    // Namespace obrigatório
    /* eslint-disable camelcase */
    M.availability_playerhud = M.availability_playerhud || {};
    /* eslint-enable camelcase */

    /**
     * @class M.availability_playerhud.form
     * @extends M.core_availability.plugin
     */
    M.availability_playerhud.form = Y.Object(M.core_availability.plugin);

    /**
     * Itens disponíveis (carregados via PHP initInner).
     */
    M.availability_playerhud.form.items = null;

    /**
     * Inicializa os dados (chamado pelo Moodle automaticamente).
     *
     * @method initInner
     * @param {Array} items Lista de itens vindos do PHP.
     */
    M.availability_playerhud.form.initInner = function(items) {
        this.items = items || [];
    };

    /**
     * Gera o nó HTML para o formulário.
     *
     * @method getNode
     * @param {Object} json Dados salvos anteriormente.
     * @return {Y.Node} O nó YUI.
     */
    M.availability_playerhud.form.getNode = function(json) {
        // Strings de idioma (Garantir que existem no PHP)
        var strLevel = M.util.get_string('option_level', 'availability_playerhud');
        var strItem = M.util.get_string('option_item', 'availability_playerhud');
        var strMin = M.util.get_string('label_min_level', 'availability_playerhud');
        var strQty = M.util.get_string('label_item_qty', 'availability_playerhud');
        var strType = M.util.get_string('label_type', 'availability_playerhud');

        // Estrutura HTML com Bootstrap 5 e classes do Moodle
        var html = '<div class="d-inline-flex align-items-center flex-wrap gap-2 border p-2 rounded bg-light">' +
                   '<label class="mb-0 fw-bold">' + strType + ' ' +
                   '<select name="subtype" class="form-select form-select-sm d-inline-block w-auto ms-1">' +
                   '<option value="level">' + strLevel + '</option>' +
                   '<option value="item">' + strItem + '</option>' +
                   '</select></label>';

        // Opção Nível
        html += '<span class="ph-option-level ms-2">' +
                '<label class="mb-0">' + strMin + ' ' +
                // eslint-disable-next-line max-len
                '<input type="number" name="levelval" class="form-control form-select-sm d-inline-block w-auto" style="width: 70px;" min="1" value="1">' +
                '</label></span>';

        // Opção Item
        html += '<span class="ph-option-item ms-2" style="display:none;">' +
                '<label class="mb-0 me-2">' +
                '<select name="itemid" class="form-select form-select-sm d-inline-block w-auto" style="max-width: 200px;">';

        if (this.items && this.items.length > 0) {
            for (var i = 0; i < this.items.length; i++) {
                html += '<option value="' + this.items[i].id + '">' + Y.Escape.html(this.items[i].name) + '</option>';
            }
        } else {
            html += '<option value="0">' + M.util.get_string('empty', 'availability_playerhud') + '</option>';
        }

        html += '</select></label>';
        html += '<label class="mb-0">' + strQty + ' ' +
                // eslint-disable-next-line max-len
                '<input type="number" name="itemqty" class="form-control form-select-sm d-inline-block w-auto" style="width: 70px;" min="1" value="1">' +
                '</label></span></div>';

        var node = Y.Node.create(html);

        // Lógica de Visibilidade
        var subtype = node.one('select[name=subtype]');
        var updateVisibility = function() {
            var val = subtype.get('value');
            if (val === 'level') {
                node.one('.ph-option-level').setStyle('display', 'inline');
                node.one('.ph-option-item').setStyle('display', 'none');
            } else {
                node.one('.ph-option-level').setStyle('display', 'none');
                node.one('.ph-option-item').setStyle('display', 'inline');
            }
        };

        subtype.on('change', function() {
            updateVisibility();
            M.core_availability.form.update();
        });

        // Preenchimento de valores (Edição)
        if (json.subtype) {
            subtype.set('value', json.subtype);
            if (json.subtype === 'item') {
                if (json.itemid) {
                    node.one('select[name=itemid]').set('value', json.itemid);
                }
                if (json.itemqty) {
                    node.one('input[name=itemqty]').set('value', json.itemqty);
                }
            } else {
                if (json.levelval) {
                    node.one('input[name=levelval]').set('value', json.levelval);
                }
            }
        }

        updateVisibility();

        // Listeners
        node.all('input, select').on('change', function() {
            M.core_availability.form.update();
        });

        return node;
    };

    /**
     * Prepara valor para salvar.
     */
    M.availability_playerhud.form.fillValue = function(value, node) {
        var subtype = node.one('select[name=subtype]').get('value');
        value.subtype = subtype;

        if (subtype === 'level') {
            value.levelval = parseInt(node.one('input[name=levelval]').get('value'), 10) || 1;
        } else {
            value.itemid = parseInt(node.one('select[name=itemid]').get('value'), 10) || 0;
            value.itemqty = parseInt(node.one('input[name=itemqty]').get('value'), 10) || 1;
        }
    };

    /**
     * Validação de Erros.
     */
    M.availability_playerhud.form.fillErrors = function(errors, node) {
        var subtype = node.one('select[name=subtype]').get('value');
        if (subtype === 'item') {
            var itemid = parseInt(node.one('select[name=itemid]').get('value'), 10);
            if (!itemid || itemid <= 0) {
                // Errors.push('availability_playerhud:error_item_required'); // Opcional
            }
        }
    };

}, '@VERSION@', {
    requires: ['base', 'node', 'event', 'moodle-core_availability-form', 'escape']
});


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form", "escape"]});
