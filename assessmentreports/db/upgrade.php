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
 * Define upgrade tasks for the plugin.
 *
 * @package   report_assessmentreports
 * @copyright 2020 onward Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool A status indicating success or failure
 */
function xmldb_report_assessmentreports_upgrade($oldversion) {
    if ($oldversion < 2020011000) {
        // Migrate 'show_username' setting to new 'fields' setting.
        $showusername = get_config('report_assessmentreports', 'show_username');

        if ($showusername) {
            set_config('fields', "fullname\nusername", 'report_assessmentreports');
        }

        upgrade_plugin_savepoint(true, 2020011000, 'report', 'assessmentreports');
    }

    if ($oldversion < 2022032900) {
        // Remove deprecated flatnav support.
        unset_config('flatnav', 'report_assessmentreports');
        unset_config('flatnav_position', 'report_assessmentreports');

        upgrade_plugin_savepoint(true, 2022032900, 'report', 'assessmentreports');
    }

    return true;
}
