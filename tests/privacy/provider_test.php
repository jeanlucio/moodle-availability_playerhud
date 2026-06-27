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

namespace availability_playerhud\privacy;

use advanced_testcase;

/**
 * Tests for the PlayerHUD availability privacy provider.
 *
 * @package    availability_playerhud
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_playerhud\privacy\provider
 */
final class provider_test extends advanced_testcase {
    /**
     * Test that the privacy provider declares no personal data via the correct lang key.
     */
    public function test_get_reason(): void {
        $this->assertSame('privacy:metadata', provider::get_reason());
    }
}
