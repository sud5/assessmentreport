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
 * Display a assessmentreports of students
 *
 * @package   report_assessmentreports
 * @copyright 2013 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot.'/lib/pdflib.php');

$id       = required_param('id', PARAM_INT);
$mode     = optional_param('mode', assessmentreports_MODE_DISPLAY, PARAM_TEXT);
$group    = optional_param('group', 0, PARAM_INT);
$role     = optional_param('role', 5, PARAM_INT);
$download     = optional_param('download', 0, PARAM_INT);
$autosize = report_assessmentreports_resolve_auto_size();
$size     = optional_param('size', $autosize, PARAM_INT);
$course   = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$action     = optional_param('action', '', PARAM_RAW);

require_login($course);

// Setup page.
$PAGE->set_url('/report/assessmentreports/index.php', array('id' => $id));
if ($mode === assessmentreports_MODE_PRINT) {
    $PAGE->set_pagelayout('print');
} else {
    $PAGE->set_pagelayout('report');
}
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/assessmentreports:view', $coursecontext);

// Get all the users.
$fieldstofetch = report_assessmentreports_profile_fields_query();
if ($role > 0) {
    $userlist = get_role_users(
        $role,
        $coursecontext,
        false,
        $fieldstofetch,
        null,
        null,
        $group
    );
} else {
    $userlist = get_enrolled_users(
        $coursecontext,
        '',
        $group,
        $fieldstofetch
    );
}

// Get suspended users.
$suspended = get_suspended_userids($coursecontext);

$data = array();
$fields = explode("\n", get_config('report_assessmentreports', 'fields'));

$studenttable = new html_table();
foreach ($userlist as $user) {
    // If user is suspended, skip them.
    if (in_array($user->id, $suspended)) {
        continue;
    }

    // Get user picture and profile data.
    $item = '<h5><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'">'.$user->firstname.'&nbsp;'.$user->lastname.'</a></h5>';

    $studenttable->data[] = [$item, '<a href="'.$CFG->wwwroot.'/report/assessmentreports/getreports.php?download=1&id='.$id.'&userid='.$user->id.'"<i class="fa fa-download"></i>'];
}


// Finish setting up page.
$PAGE->set_title($course->shortname .': '. get_config('report_assessmentreports' , 'displayname'));
$PAGE->set_heading($course->fullname);
$html = '';
$html .= '<h3 style="color:#EC8E4A">Assessment & Attendance Report</h3>';

$html .= '<div class="buttonwrapper" >
    <a href="'.$CFG->wwwroot.'/report/assessmentreports/getreports.php?id='.$course->id.'&download=2"><button class="btn btn-primary" style="float:right;margin-bottom:20px;">Download all <i class="fa fa-download"></i></button></a>
</div>';
$html .= html_writer::table($studenttable);

echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();
