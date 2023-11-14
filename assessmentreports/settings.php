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
 * Library functions for the assessmentreports report.
 *
 * @package   report_assessmentreports
 * @copyright 2019 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(
        new admin_setting_heading('general', get_string('settings:headings:general', 'report_assessmentreports'), '')
    );

    $settings->add(
        new admin_setting_configtextarea('report_assessmentreports/fields',
            get_string('settings:fields', 'report_assessmentreports'),
            get_string('settings:fields:description', 'report_assessmentreports'),
            get_string('settings:fields:default', 'report_assessmentreports')
        )
    );

    $settings->add(
        new admin_setting_configtext('report_assessmentreports/displayname',
            get_string('settings:displayname', 'report_assessmentreports'),
            get_string('settings:displayname:description', 'report_assessmentreports'),
            get_string('settings:displayname:default', 'report_assessmentreports')
        )
    );

    $options = array(
        'small'  => get_string('size:small', 'report_assessmentreports'),
        'medium' => get_string('size:medium', 'report_assessmentreports'),
        'large'  => get_string('size:large', 'report_assessmentreports'),
    );

    $settings->add(
        new admin_setting_heading('size', get_string('settings:headings:size', 'report_assessmentreports'), '')
    );

    $settings->add(
        new admin_setting_configselect('report_assessmentreports/size_default',
            get_string('settings:size_default', 'report_assessmentreports'),
            get_string('settings:size_default:description', 'report_assessmentreports'),
            'small',
            $options
        )
    );

    $settings->add(
        new admin_setting_configtext('report_assessmentreports/size_small',
            get_string('settings:size_small', 'report_assessmentreports'),
            get_string('settings:size_small:description', 'report_assessmentreports'),
            100,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext('report_assessmentreports/size_medium',
            get_string('settings:size_medium', 'report_assessmentreports'),
            get_string('settings:size_medium:description', 'report_assessmentreports'),
            200,
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_configtext('report_assessmentreports/size_large',
            get_string('settings:size_large', 'report_assessmentreports'),
            get_string('settings:size_large:description', 'report_assessmentreports'),
            300,
            PARAM_INT
        )
    );
}
