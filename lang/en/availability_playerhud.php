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
 * Language strings for availability_playerhud (English).
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['description'] = 'Allow access based on Level, Items or Character in PlayerHUD.';
$string['empty'] = 'No items found';
$string['error_block_missing'] = 'PlayerHUD block not found in this course.';
$string['label_class_select'] = 'Required Character:';
$string['label_item_qty'] = 'Minimum Quantity:';
$string['label_item_select'] = 'Required Item:';
$string['label_min_level'] = 'Required Level:';
$string['label_type'] = 'Type:';
$string['missing'] = '(Missing data)';
$string['op_atleast'] = 'at least';
$string['op_equal'] = 'exactly';
$string['op_less'] = 'less than';
$string['op_more'] = 'more than';
$string['op_text_atleast'] = 'at least';
$string['op_text_equal'] = 'exactly';
$string['op_text_less'] = 'less than';
$string['op_text_more'] = 'more than';
$string['option_class'] = 'Character';
$string['option_item'] = 'Own Item';
$string['option_level'] = 'Minimum Level';
$string['pluginname'] = 'PlayerHUD Availability Condition';
$string['privacy:metadata'] = 'The PlayerHUD Availability Condition plugin does not store any personal data. It evaluates access rules based on data provided by the PlayerHUD Block.';
$string['requires_class'] = 'Your character must be <strong>{$a}</strong>.';
$string['requires_item'] = 'You must have <strong>{$a->op} {$a->qty}x {$a->item}</strong>.';
$string['requires_level'] = 'You must reach <strong>Level {$a}</strong>.';
$string['title'] = 'PlayerHUD';
