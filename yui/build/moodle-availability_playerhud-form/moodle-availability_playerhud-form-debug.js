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
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} items List of items available in PlayerHUD block.
 */
M.availability_playerhud.form.initInner = function(items) {
    this.items = items || [];
};

/**
 * Creates the HTML node for the form.
 *
 * @method getNode
 * @param {Object} json The existing data for this restriction.
 * @return {Y.Node} The YUI node.
 */
M.availability_playerhud.form.getNode = function(json) {
    // Rule 4: No hardcoded strings. Fetching from language pack.
    var s = {
        level: M.util.get_string('option_level', 'availability_playerhud'),
        item: M.util.get_string('option_item', 'availability_playerhud'),
        min_level: M.util.get_string('label_min_level', 'availability_playerhud'),
        qty: M.util.get_string('label_item_qty', 'availability_playerhud'),
        type: M.util.get_string('label_type', 'availability_playerhud'),
        empty: M.util.get_string('empty', 'availability_playerhud'),
        more: M.util.get_string('op_more', 'availability_playerhud'),
        less: M.util.get_string('op_less', 'availability_playerhud'),
        equal: M.util.get_string('op_equal', 'availability_playerhud')
    };

    // Rule 1 & 14: Bootstrap 5 classes (gap-2 handles the vertical/horizontal spacing).
    var html = '<span class="ph-controls d-inline-flex flex-wrap align-items-center gap-2">';

    // Type Selection
    // Rule 8: Implicit label wrapping.
    html += '<label class="mb-0">' + s.type + ' ';
    html += '<select name="subtype" class="form-select form-select-sm d-inline-block w-auto ms-1">';
    html += '<option value="level">' + s.level + '</option>';
    html += '<option value="item">' + s.item + '</option>';
    html += '</select></label>';

    // --- Option: Level ---
    html += '<span class="ph-option-level">';
    html += '<label class="mb-0">' + s.min_level + ' ';
    // Rule 11: Class .ph-input-qty used for width.
    html += '<input type="number" name="levelval" class="form-control form-select-sm d-inline-block ph-input-qty ms-1" ';
    html += 'min="1" value="1">';
    html += '</label></span>';

    // --- Option: Item ---
    html += '<span class="ph-option-item">';

    // Item Selection
    html += '<label class="mb-0">';
    // Rule 11: Class .ph-select-item used for max-width.
    html += '<select name="itemid" class="form-select form-select-sm d-inline-block ph-select-item">';

    if (this.items && this.items.length > 0) {
        for (var i = 0; i < this.items.length; i++) {
            html += '<option value="' + this.items[i].id + '">' + Y.Escape.html(this.items[i].name) + '</option>';
        }
    } else {
        html += '<option value="0">' + s.empty + '</option>';
    }
    html += '</select></label>';

    // Operator Selection
    html += '<label class="mb-0">';
    html += '<span class="visually-hidden">' + s.type + '</span>'; // Rule 6/8: Accessibility
    html += '<select name="itemop" class="form-select form-select-sm d-inline-block w-auto">';
    html += '<option value=">">' + s.more + '</option>';
    html += '<option value="<">' + s.less + '</option>';
    html += '<option value="=">' + s.equal + '</option>';
    html += '</select></label>';

    // Quantity Input
    html += '<label class="mb-0">';
    html += '<span class="visually-hidden">' + s.qty + '</span>'; // Rule 8: Accessibility
    html += '<input type="number" name="itemqty" class="form-control form-select-sm d-inline-block ph-input-qty" ';
    html += 'min="1" value="1">';
    html += '</label></span>';

    html += '</span>'; // End container

    var node = Y.Node.create(html);

    // Visibility Logic
    var subtype = node.one('select[name=subtype]');
    var updateVisibility = function() {
        var val = subtype.get('value');
        if (val === 'level') {
            node.one('.ph-option-level').setStyle('display', 'contents'); // 'contents' works better with flex gap
            node.one('.ph-option-item').setStyle('display', 'none');
        } else {
            node.one('.ph-option-level').setStyle('display', 'none');
            node.one('.ph-option-item').setStyle('display', 'contents');
        }
    };

    subtype.on('change', function() {
        updateVisibility();
        M.core_availability.form.update();
    });

    // Fill values if editing existing restriction
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
        } else {
            if (json.levelval) {
                node.one('input[name=levelval]').set('value', json.levelval);
            }
        }
    }

    updateVisibility();

    // Event Listeners for auto-update
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
    } else {
        value.itemid = parseInt(node.one('select[name=itemid]').get('value'), 10) || 0;
        value.itemqty = parseInt(node.one('input[name=itemqty]').get('value'), 10) || 1;
        value.itemop = node.one('select[name=itemop]').get('value');
    }
};

/**
 * Handles form validation errors.
 *
 * @method fillErrors
 * @param {Array} errors Array to push errors to.
 * @param {Y.Node} node The form node.
 */
M.availability_playerhud.form.fillErrors = function(errors, node) {
    // Optional validation logic
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form", "escape"]});
