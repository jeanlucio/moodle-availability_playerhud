YUI.add('moodle-availability_playerhud-form', function (Y, NAME) {

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
 * PlayerHUD Availability Form.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint-disable camelcase */
/**
 * PlayerHUD Availability Form.
 *
 * @module moodle-availability_playerhud-form
 */
M.availability_playerhud = M.availability_playerhud || {};

/**
 * @class M.availability_playerhud.form
 * @extends M.core_availability.plugin
 */
M.availability_playerhud.form = Y.Object(M.core_availability.plugin);

/**
 * Initialized items from PHP.
 * @property items
 * @type Array
 */
M.availability_playerhud.form.items = null;

/**
 * Initialized classes from PHP.
 * @property classes
 * @type Array
 */
M.availability_playerhud.form.classes = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} items List of items available in PlayerHUD block.
 * @param {Array} classes List of RPG classes available in PlayerHUD block.
 */
M.availability_playerhud.form.initInner = function(items, classes) {
    this.items = items || [];
    this.classes = classes || [];
};

/**
 * Creates the HTML node for the form.
 *
 * @method getNode
 * @param {Object} json The existing data for this restriction.
 * @return {Y.Node} The YUI node.
 */
M.availability_playerhud.form.getNode = function(json) {
    var s = {
        level: M.util.get_string('option_level', 'availability_playerhud'),
        item: M.util.get_string('option_item', 'availability_playerhud'),
        rpgclass: M.util.get_string('option_class', 'availability_playerhud'),
        min_level: M.util.get_string('label_min_level', 'availability_playerhud'),
        qty: M.util.get_string('label_item_qty', 'availability_playerhud'),
        type: M.util.get_string('label_type', 'availability_playerhud'),
        empty: M.util.get_string('empty', 'availability_playerhud'),
        atleast: M.util.get_string('op_atleast', 'availability_playerhud'),
        more: M.util.get_string('op_more', 'availability_playerhud'),
        less: M.util.get_string('op_less', 'availability_playerhud'),
        equal: M.util.get_string('op_equal', 'availability_playerhud'),
        class_select: M.util.get_string('label_class_select', 'availability_playerhud')
    };

    var html = '<span class="ph-controls d-inline-flex flex-wrap align-items-center gap-2">';
    html += '<label class="mb-0">' + s.type + ' ';
    html += '<select name="subtype" class="form-select form-select-sm d-inline-block w-auto ms-1">';
    html += '<option value="level">' + s.level + '</option>';
    html += '<option value="item">' + s.item + '</option>';
    html += '<option value="class">' + s.rpgclass + '</option>';
    html += '</select></label>';

    html += '<span class="ph-option-level">';
    html += '<label class="mb-0">' + s.min_level + ' ';
    html += '<input type="number" name="levelval" class="form-control form-select-sm d-inline-block ph-input-qty ms-1" ';
    html += 'min="1" value="1">';
    html += '</label></span>';

    html += '<span class="ph-option-item">';
    html += '<label class="mb-0">';
    html += '<select name="itemid" class="form-select form-select-sm d-inline-block ph-select-item">';

    var i;
    if (this.items && this.items.length > 0) {
        for (i = 0; i < this.items.length; i++) {
            html += '<option value="' + this.items[i].id + '">' + Y.Escape.html(this.items[i].name) + '</option>';
        }
    } else {
        html += '<option value="0">' + s.empty + '</option>';
    }
    html += '</select></label>';

    html += '<label class="mb-0">';
    html += '<span class="visually-hidden">' + s.type + '</span>';
    html += '<select name="itemop" class="form-select form-select-sm d-inline-block w-auto">';
    html += '<option value=">=">' + s.atleast + '</option>';
    html += '<option value=">">' + s.more + '</option>';
    html += '<option value="<">' + s.less + '</option>';
    html += '<option value="=">' + s.equal + '</option>';
    html += '</select></label>';

    html += '<label class="mb-0">';
    html += '<span class="visually-hidden">' + s.qty + '</span>';
    html += '<input type="number" name="itemqty" class="form-control form-select-sm d-inline-block ph-input-qty" ';
    html += 'min="1" value="1">';
    html += '</label></span>';

    html += '<span class="ph-option-class">';
    html += '<label class="mb-0">' + s.class_select + ' ';
    html += '<select name="classid" class="form-select form-select-sm d-inline-block ph-select-class ms-1">';

    if (this.classes && this.classes.length > 0) {
        for (i = 0; i < this.classes.length; i++) {
            html += '<option value="' + this.classes[i].id + '">' + Y.Escape.html(this.classes[i].name) + '</option>';
        }
    } else {
        html += '<option value="0">' + s.empty + '</option>';
    }
    html += '</select></label>';
    html += '</span>';

    html += '</span>';

    var node = Y.Node.create(html);
    var subtype = node.one('select[name=subtype]');

    var updateVisibility = function() {
        var val = subtype.get('value');
        node.one('.ph-option-level').setStyle('display', val === 'level' ? 'contents' : 'none');
        node.one('.ph-option-item').setStyle('display', val === 'item' ? 'contents' : 'none');
        node.one('.ph-option-class').setStyle('display', val === 'class' ? 'contents' : 'none');
    };

    subtype.on('change', function() {
        updateVisibility();
        M.core_availability.form.update();
    });

    if (json.subtype) {
        subtype.set('value', json.subtype);
        if (json.subtype === 'item') {
            if (json.itemid) {
                node.one('select[name=itemid]').set('value', json.itemid);
            }
            if (json.itemqty) {
                node.one('input[name=itemqty]').set('value', json.itemqty);
            }
            if (json.itemop) {
                node.one('select[name=itemop]').set('value', json.itemop);
            }
        } else if (json.subtype === 'class') {
            if (json.classid) {
                node.one('select[name=classid]').set('value', json.classid);
            }
        } else if (json.levelval) {
            node.one('input[name=levelval]').set('value', json.levelval);
        }
    }

    updateVisibility();

    node.all('input, select').on('change', function() {
        M.core_availability.form.update();
    });

    return node;
};

/**
 * Fills the value object with data from the form.
 *
 * @method fillValue
 * @param {Object} value The object to populate.
 * @param {Y.Node} node The form node.
 */
M.availability_playerhud.form.fillValue = function(value, node) {
    var subtype = node.one('select[name=subtype]').get('value');
    value.subtype = subtype;

    if (subtype === 'level') {
        value.levelval = parseInt(node.one('input[name=levelval]').get('value'), 10) || 1;
    } else if (subtype === 'class') {
        value.classid = parseInt(node.one('select[name=classid]').get('value'), 10) || 0;
    } else {
        value.itemid = parseInt(node.one('select[name=itemid]').get('value'), 10) || 0;
        value.itemqty = parseInt(node.one('input[name=itemqty]').get('value'), 10) || 1;
        value.itemop = node.one('select[name=itemop]').get('value');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form", "escape"]});
