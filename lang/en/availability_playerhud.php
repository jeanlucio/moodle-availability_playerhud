<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PlayerHUD Restriction';
$string['description'] = 'Allow access based on Level or Items collected in PlayerHUD.';
$string['title'] = 'PlayerHUD';
$string['option_level'] = 'Minimum Level';
$string['option_item'] = 'Own Item';
$string['label_min_level'] = 'Required Level:';
$string['label_item_select'] = 'Required Item:';
$string['label_item_qty'] = 'Minimum Quantity:';
$string['missing'] = '(Missing data)';
$string['requires_level'] = 'You must reach <strong>Level {$a}</strong>.';
$string['requires_item'] = 'You must have <strong>{$a->qty}x {$a->item}</strong>.';
$string['error_block_missing'] = 'PlayerHUD block not found in this course.';
