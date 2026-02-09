/**
 * JavaScript for PlayerHUD availability condition.
 *
 * @module moodle-availability_playerhud-form
 */
YUI.add('moodle-availability_playerhud-form', function(Y) {
    // Namespace obrigatório (com disable do camelcase do Eslint).
    /* eslint-disable camelcase */
    M.availability_playerhud = M.availability_playerhud || {};
    /* eslint-enable camelcase */

    /**
     * Define a lógica do formulário estendendo o plugin base do core.
     */
    M.availability_playerhud.form = Y.Object(M.core_availability.plugin);

    /**
     * Inicializa os dados (chamado pelo Moodle automaticamente).
     *
     * @method initInner
     * @param {Array} items Dados passados do PHP.
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
        // HTML Template com melhor legibilidade e concatenação.
        var html = '<div class="d-inline-block border p-2 rounded bg-light">' +
                   '<label class="me-2 fw-bold">Tipo: ' +
                   '<select name="subtype" class="form-select d-inline-block w-auto me-3">' +
                   '<option value="level">Nível (Level)</option>' +
                   '<option value="item">Item</option>' +
                   '</select></label>';

        html += '<span class="ph-option-level">' +
                '<label>Nível Mínimo: <input type="number" name="levelval" ' +
                'class="form-control d-inline-block w-auto" style="width:80px;" min="1" value="1"></label>' +
                '</span>';

        html += '<span class="ph-option-item hidden" style="display:none;">' +
                '<label class="me-2">Item: ' +
                '<select name="itemid" class="form-select d-inline-block w-auto me-2" style="max-width:200px;">';

        // Popula itens com escape de segurança.
        if (this.items && this.items.length > 0) {
            for (var i = 0; i < this.items.length; i++) {
                var itemName = Y.Escape.html(this.items[i].name);
                html += '<option value="' + this.items[i].id + '">' + itemName + '</option>';
            }
        } else {
            // Usa a string 'empty' que adicionamos aos arquivos de idioma.
            html += '<option value="0">(' + M.util.get_string('empty', 'availability_playerhud') + ')</option>';
        }

        html += '</select></label>';
        html += '<label>Qtd: <input type="number" name="itemqty" class="form-control d-inline-block w-auto" ' +
                'style="width:70px;" min="1" value="1"></label>' +
                '</span></div>';

        var node = Y.Node.create(html);

        // Lógica de troca de visibilidade.
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

        // Preencher valores (Edição).
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

        // Executar inicialização visual.
        updateVisibility();

        // Listeners para atualização da árvore.
        node.all('input, select').on('change', function() {
            M.core_availability.form.update();
        });

        return node;
    };

    /**
     * Preenche o objeto de valor para ser salvo.
     *
     * @method fillValue
     * @param {Object} value O objeto JSON.
     * @param {Y.Node} node O nó HTML.
     */
    M.availability_playerhud.form.fillValue = function(value, node) {
        var subtype = node.one('select[name=subtype]').get('value');
        value.subtype = subtype;

        if (subtype === 'level') {
            var lvl = parseInt(node.one('input[name=levelval]').get('value'), 10);
            value.levelval = isNaN(lvl) ? 1 : lvl;
        } else {
            var itemid = parseInt(node.one('select[name=itemid]').get('value'), 10);
            var qty = parseInt(node.one('input[name=itemqty]').get('value'), 10);
            value.itemid = isNaN(itemid) ? 0 : itemid;
            value.itemqty = isNaN(qty) ? 1 : qty;
        }
    };

    /**
     * Validação de erros antes de salvar.
     *
     * @method fillErrors
     * @param {Array} errors Lista de erros.
     * @param {Y.Node} node O nó HTML.
     */
    M.availability_playerhud.form.fillErrors = function(errors, node) {
        var subtype = node.one('select[name=subtype]').get('value');
        if (subtype === 'item') {
            var itemid = parseInt(node.one('select[name=itemid]').get('value'), 10);
            if (itemid <= 0) {
                // Erro se tentar salvar restrição de item sem ter itens criados.
                errors.push('availability_playerhud:error_item_required');
            }
        }
    };

}, '@VERSION@', {
    requires: ['base', 'node', 'event', 'moodle-core_availability-form', 'escape']
});
