/**
 * JavaScript for PlayerHUD availability condition.
 *
 * @module moodle-availability_playerhud-form
 */
YUI.add('moodle-availability_playerhud-form', function(Y) {

    // Define a classe do formulário.
    // O Moodle exige este namespace exato, então desativamos o aviso de camelcase.
    /* eslint-disable camelcase */
    M.availability_playerhud = M.availability_playerhud || {};
    /* eslint-enable camelcase */

    M.availability_playerhud.form = Y.Base.create('moodle-availability_playerhud-form', Y.Plugin.Base, [], {

        /**
         * Inicializa o formulário.
         *
         * @method initializer
         * @param {Object} params
         */
        initializer: function(params) {
            this.items = params.items; // Recebe a lista de itens do PHP.
        },

        /**
         * Cria os nós HTML (Inputs, Selects).
         *
         * @method getNode
         * @param {Object} json
         * @return {Y.Node}
         */
        getNode: function(json) {
            // 1. Selector de TIPO (Nível ou Item).
            var html = '<label class="me-2">Tipo: <select name="subtype" class="form-select d-inline-block w-auto me-3">' +
                       '<option value="level">Nível</option>' +
                       '<option value="item">Item</option>' +
                       '</select></label>';

            // 2. Opções de NÍVEL.
            html += '<span class="ph-option-level">' +
                    '<label>Nível Mínimo: <input type="number" name="levelval" ' +
                    'class="form-control d-inline-block w-auto" style="width:80px;" min="1" value="1"></label>' +
                    '</span>';

            // 3. Opções de ITEM.
            html += '<span class="ph-option-item" style="display:none;">' +
                    '<label class="me-2">Item: <select name="itemid" class="form-select d-inline-block w-auto me-2">';

            // Popula itens.
            if (this.items && this.items.length > 0) {
                for (var i = 0; i < this.items.length; i++) {
                    html += '<option value="' + this.items[i].id + '">' + Y.Escape.html(this.items[i].name) + '</option>';
                }
            } else {
                html += '<option value="0">Nenhum item encontrado no curso</option>';
            }

            html += '</select></label>';
            html += '<label>Qtd: <input type="number" name="itemqty" class="form-control d-inline-block w-auto" ' +
                    'style="width:70px;" min="1" value="1"></label>' +
                    '</span>';

            var node = Y.Node.create('<span>' + html + '</span>');

            // Event Listener para trocar visualização.
            var subtype = node.one('select[name=subtype]');
            subtype.on('change', function() {
                if (subtype.get('value') === 'level') {
                    node.one('.ph-option-level').show();
                    node.one('.ph-option-item').hide();
                } else {
                    node.one('.ph-option-level').hide();
                    node.one('.ph-option-item').show();
                }
            });

            // Preenche valores se estiver editando.
            if (json.subtype) {
                subtype.set('value', json.subtype);
                if (json.subtype === 'item') {
                    node.one('.ph-option-level').hide();
                    node.one('.ph-option-item').show();
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

            // Moodle requer handlers para atualização dinâmica.
            if (!M.availability_playerhud.form.addedEvents) {
                M.availability_playerhud.form.addedEvents = true;
                var root = Y.one('#fitem_id_availabilityconditionsjson');
                root.delegate('change', function() {
                    M.core_availability.form.update();
                }, '.availability_playerhud select');
                root.delegate('change', function() {
                    M.core_availability.form.update();
                }, '.availability_playerhud input');
            }

            return node;
        },

        /**
         * Prepara os dados do formulário para salvar no JSON.
         *
         * @method fillValue
         * @param {Object} value
         * @param {Y.Node} node
         */
        fillValue: function(value, node) {
            var subtype = node.one('select[name=subtype]').get('value');
            value.subtype = subtype;

            if (subtype === 'level') {
                value.levelval = parseInt(node.one('input[name=levelval]').get('value'), 10);
            } else {
                value.itemid = parseInt(node.one('select[name=itemid]').get('value'), 10);
                value.itemqty = parseInt(node.one('input[name=itemqty]').get('value'), 10);
            }
        }
    }, {
        NAME: 'availability_playerhud-form',
        ATTRS: {
            connection: {
                getter: function() {
                    return this;
                }
            }
        }
    });

}, '@VERSION@', {requires: ['base', 'node', 'event', 'io', 'moodle-core_availability-form']});
