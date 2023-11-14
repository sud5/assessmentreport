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
 * Local library functions for the assessmentreports report.
 *
 * @package   report_assessmentreports
 * @copyright 2013 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('assessmentreports_MODE_DISPLAY', 'display');
define('assessmentreports_MODE_PRINT', 'print');

/**
 * Retrieves the groups for the course and formats them for use in a drop-down
 * selector.
 *
 * @param int $id The course id
 * @return array The course groups indexed by group id
 */
function report_assessmentreports_get_options_group($id) {
    $groupsfromdb = groups_get_all_groups($id);
    $groups = array(0 => get_string('allusers', 'report_assessmentreports'));
    foreach ($groupsfromdb as $key => $value) {
        $groups[$key] = $value->name;
    }
    return $groups;
}

/**
 * Retrieves the roles for the course and formats them for use in a drop-down
 * selector.
 *
 * @param int $id The course id
 * @return array The course roles indexed by role id
 */
function report_assessmentreports_get_options_role($id) {
    global $USER;

    $context       = context_course::instance($id);
    $rolesfromdb   = get_roles_used_in_context($context);
    $viewableroles = get_viewable_roles($context, $USER->id);

    $roles = array(0 => get_string('allusers', 'report_assessmentreports'));
    foreach ($rolesfromdb as $role) {
        $rolename = $viewableroles[$role->id];
        if ($rolename) {
            $roles[$role->id] = $rolename;
        }
    }
    return $roles;
}

/**
 * Retrieves the size options and formats them for use in a drop-down
 * selector.
 *
 * @return array The user image size options
 */
function report_assessmentreports_get_options_size() {
    $sizes = array();

    foreach (array('small', 'medium', 'large') as $size) {
        $pixels = (int) get_config('report_assessmentreports', "size_$size");
        $label  = get_string("size:$size", 'report_assessmentreports');

        if ($pixels > 0) {
            $sizes[$pixels] = $label;
        }
    }

    return $sizes;
}

/**
 * Creates the action buttons (learning mode and groups) used on the report page.
 *
 * @param int $id The course id
 * @param moodle_url $url The current page URL
 * @param array $params Current parameters values as an associative array (group, role, size, mode)
 * @return string The generated HTML
 */
function report_assessmentreports_output_action_buttons($id, $url, $params) {
    global $OUTPUT;

    $options = array();
    $options   = [''=>'Select option', 1=>'PDF', 2=>'Excel'];  

    $myselect        = new single_select($url, 'download', $options, $val, null);
    $myselect->label = 'Download As';

    $html = html_writer::start_tag('div style="width:100%;text-align:right;"');
   
    $html .= $OUTPUT->render($myselect);
   
    $html .= html_writer::end_tag('div');
    return $html;
}

/**
 * Returns the value of the given field for the given user. Returns false
 * if the field does not exist on the user object.
 *
 * @param string $field the user field name
 * @param stdClass $user the user object
 *
 * @return string the value of the field
 */
function report_assessmentreports_process_field($field, $user) {
    $field = trim($field);
    if ($field == 'fullname') {
        return fullname($user);
    } else if (strpos($field, 'currenttime') === 0) {
        $format = trim(str_replace('currenttime', '', $field));
        return userdate(time(), $format, $user->timezone);
    } else if (property_exists($user, $field) && !empty($user->{$field}) && is_string($user->{$field})) {
        return $user->{$field};
    }
    return false;
}

/**
 * Resolves which size display when no query param has been passed.
 *
 * @return string The generated HTML
 */
function report_assessmentreports_resolve_auto_size() {
    $defaultselector = get_config('report_assessmentreports', 'size_default');
    $defaultsize = get_config('report_assessmentreports', 'size_' . $defaultselector);

    if ($defaultsize != 0) {
        // If the default size config is valid, return that.
        return (int) $defaultsize;
    } else {
        // Otherwise, check the other size options and return the first non-zero one.
        foreach (array('small', 'medium', 'large') as $selector) {
            $size = get_config('report_assessmentreports', 'size_' . $selector);
            if ($size != 0) {
                return $size;
            }
        }
        // And finally, if none of that worked, hard default to 100.
        return 100;
    }
}

/**
 * Build the fields to retrieve from the user profile.
 *
 * @return string SQL-query-like string of fields to fetch, for use in get_enrolled_users / get_role_users
 */
function report_assessmentreports_profile_fields_query() {
    global $DB, $USER;

    // Set extra fields to retrieve.
    $extrafields = ['username', 'timezone'];
    $fieldsconfig = explode("\n", get_config('report_assessmentreports', 'fields'));
    foreach ($fieldsconfig as $field) {
        $field = trim($field);

        if ( property_exists($USER, $field)) {
            $extrafields[] = $field;
        }
    }

    $fields = \core_user\fields::for_userpic();
    $fields->including(...$extrafields);

    $selects = $fields->get_sql('u', false, '', 'id', false)->selects;
    $selects = str_replace(', ', ',', $selects);
    return $selects;
}

function get_grade_setup_data($courseid, $iteminfo, $userid) {
    global $DB;
    $intial_data = array();
    $data = array();
    $result = new stdClass();
//    $sql = "SELECT g.*,gi.itemname, s.scale FROM {grade_items} gi left join {grade_grades} g on g.itemid = gi.id left join {scale} s on s.id = gi.scaleid where gi.courseid = $courseid "
//            . "AND g.userid = $userid AND gi.iteminfo = '" . $iteminfo . "'";
    $sql = "SELECT gi.*, s.scale,(select finalgrade from {grade_grades} as g where gi.id = g.itemid"
            . " AND g.userid = $userid) as finalgrade FROM {grade_items} gi left join"
            . " {scale} s on s.id = gi.scaleid where gi.courseid = $courseid AND gi.iteminfo = '" . $iteminfo . "'";
    
    $records = $DB->get_records_sql($sql);
    if (!empty($records)) {
        foreach ($records as &$record) {
            $obj = new stdClass();
            $intial_data['itemname'] = $record->itemname;
            $scale = explode(",", $record->scale);
            $intial_data['grade'] = is_null($record->finalgrade) ? "" : $scale[(int)$record->finalgrade-1];
            array_push($data, $intial_data);
        }
    }
    
    $result->data = $data;
    return $result;
}

function get_attendance_setup_data($courseid, $userid) {
    global $DB;
    $sql = "SELECT ats.id as sessionid,a.id as attendanceid,cm.id, from_unixtime(ats.sessdate,'%d %M %Y') as session_date,"
            . " (select al.statusid from {attendance_log} al where al.sessionid = ats.id and al.studentid = $userid) as statusid,"
            . " (select grade from {attendance_statuses} where id=statusid) as grade, (select acronym from {attendance_statuses}"
            . " where id=statusid) as acronym FROM {attendance} a left JOIN {course_modules} as cm on cm.instance = a.id "
            . "left join {modules} m on cm.module = m.id left join {attendance_sessions} ats on a.id = ats.attendanceid "
            . "WHERE a.course = $courseid and m.name = 'attendance' and cm.deletioninprogress = 0";

    $records = $DB->get_records_sql($sql);
    $obj = new stdClass();
    if (!empty($records)) {
        $total_grades = $P = $L = $E = $A = 0;
        $intial_data = array();
        $data = array();
        foreach ($records as &$record) {

            $intial_data['session_name'] = $record->session_date;
            $grade = (int) $record->grade;
            $intial_data['session_value'] = (!empty($record->acronym)) ? $record->acronym . "($grade/2)" : "?";
            array_push($data, $intial_data);
            if ($record->acronym) {
                switch ($record->acronym) {
                    case 'P' : $P += 1;
                        break;
                    case 'L' : $L += 1;
                        break;
                    case 'E' : $E += 1;
                        break;
                    case 'A' : $A += 1;
                        break;
                    default:
                }
            }
            $obj->data = $data;
            $total_grades += $grade;
        }
        $obj->P = $P;
        $obj->L = $L;
        $obj->E = $E;
        $obj->A = $A;
        $obj->taken_sessions = $P + $L + $E + $A;
        $points = 2 * count($records);
        $obj->points = $total_grades . "/" . $points;
        $obj->percentage = number_format(($total_grades * 100) / $points, 2);
    }
    return $obj;
}

function get_teachers_data($courseid) {
    global $DB;
    $teacherstr = "";
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $get_users_query = "SELECT u.id,concat(u.firstname,' ',u.lastname) as fullname FROM mdl_user u INNER JOIN mdl_role_assignments ra ON ra.userid = u.id"
            . " INNER JOIN mdl_context ct ON ct.id = ra.contextid INNER JOIN mdl_course c ON c.id =ct.instanceid INNER JOIN"
            . " mdl_role r ON r.id = ra.roleid INNER JOIN mdl_course_categories cc ON cc.id = c.category"
            . " WHERE r.id = $role->id and c.id = $courseid";
    $records = $DB->get_records_sql_menu($get_users_query);
    if (!empty($records))
        $teachers = implode(", ", $records);
    return $teachers;
}


