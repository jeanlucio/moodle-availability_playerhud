<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for availability_playerhud.
 *
 * @package    availability_playerhud
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs the upgrade steps of availability_playerhud.
 *
 * @param int $oldversion Version being upgraded from.
 * @return bool
 */
function xmldb_availability_playerhud_upgrade(int $oldversion): bool {
    if ($oldversion < 2026101500) {
        // Restrictions re-saved by a restore or a duplicated activity lost their condition type.
        \availability_playerhud\local\availability_repair::repair_all();
        upgrade_plugin_savepoint(true, 2026101500, 'availability', 'playerhud');
    }
    return true;
}
