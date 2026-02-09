<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'availability_playerhud';
$plugin->version   = 2026020801;
$plugin->requires  = 2022112800; // Moodle 4.1+
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'v0.1';
$plugin->dependencies = ['block_playerhud' => 2026012901]; // Forces the matching mod version.
