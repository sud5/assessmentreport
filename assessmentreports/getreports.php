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
require_once($CFG->dirroot . '/lib/pdflib.php');

$id = required_param('id', PARAM_INT);
$mode = optional_param('mode', assessmentreports_MODE_DISPLAY, PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);
$role = optional_param('role', 0, PARAM_INT);
$download = optional_param('download', 0, PARAM_INT);
$autosize = report_assessmentreports_resolve_auto_size();
$size = optional_param('size', $autosize, PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course);

// Setup page.
$PAGE->set_url('/report/assessmentreports/getreports.php', array('id' => $id, 'userid' => $userid));
if ($mode === assessmentreports_MODE_PRINT) {
    $PAGE->set_pagelayout('print');
} else {
    $PAGE->set_pagelayout('report');
}
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/assessmentreports:view', $coursecontext);

if ($download == 2) {
    $users = get_role_users(
            5,
            $coursecontext,
            false,
            '',
            null,
            null,
            0
    );
    $tempdir = make_temp_directory('reports');
} else if ($download == 1) {

    $user = new stdClass();
    $user->id = $userid;
    $users[] = $user;
}

// Zip file initialisation
$zip = new ZipArchive();
$filename = $CFG->dataroot . '/temp/reports/reports.zip';
$zip->open($filename, ZipArchive::CREATE);

foreach ($users as $user) {
    $userrecord = $DB->get_record('user', array('id' => $user->id));
    $html = '';

    $html .= html_writer::tag('img', '', array('src' => $CFG->wwwroot . '/report/assessmentreports/pix/headerimage.jpeg', 'class' => 'headimg',));

    $html .= '<h4 style="background-color:#F4F7FE;">Student Details</h4>';
    $teachers = get_teachers_data($COURSE->id);
    $table = new html_table();
    $table->head = array();
    $records = [['Last Name', $userrecord->lastname],
        ['Teacher', $teachers],
        ['Year / Branch', $COURSE->fullname]];
    for ($i = 0; $i <= count($records); ++$i) {
        $table->data[] = [$records[$i][0], $records[$i][1], $records[$i + 1][0], $records[$i + 1][1]];
        $i++;
    }

    $coursename = $COURSE->fullname;
    if(strlen($coursename) > 50){
        $coursename = substr($coursename,0,48)."..";
    }
    $space = 5;
    if(strlen($userrecord->lastname) < 10 && strlen($userrecord->lastname) < 10) $space = 10;
    $a = $space + strlen($userrecord->lastname);
    $b = $space +  strlen($userrecord->firstname);

    $html .= 'First Name: ' . $userrecord->firstname . str_repeat('&nbsp;', 1.6*$a) . 'Email:' . $userrecord->email . '<br>';
    $html .= 'Last Name: ' . $userrecord->lastname . str_repeat('&nbsp;', 1.6*$b) . 'Year / Branch:' . $coursename . '<br>';
    $html .= 'Teacher: ' . $teachers;
//        $html .= '<table>
//                    <tr>
//                    <td style="line-height: 0.8;font-size=0.875em;">' . html_writer::table($table) . '</td>
//                    </tr>
//                </table>';
    // ============================Grade table=====================//
    $html .= '<h4 style="background-color:#F4F7FE;">Assessment Grades</h4>';

    $gradetable = new html_table('', array('style' => ''));
    //$gradetable->head = array();

    $graderecords = get_grade_setup_data($COURSE->id, 'Transcript-Assessment', $user->id);
    foreach ($graderecords->data as $grecord) {
        $gradetable->data[] = [$grecord['itemname'], $grecord['grade']];
    }

    //Grade abbreviations table
    $gradeabbreviationstable = new html_table('gradeabbreviationstable', array('style' => 'background-color:#C5FFB8;'));
    $gradeabbrevationrecords = [
        ['EE', 'Exceed Expectations'],
        ['ME-S', 'Meeting Expectations-Strong'],
        ['ME-M	', 'Meeting Expectations-Minimal'],
        ['D', 'Does not meet expectations']];

    foreach ($gradeabbrevationrecords as $grecord) {
        $gradeabbreviationstable->data[] = [$grecord[0], $grecord[1]];
    }

    // Grade abbreviations table end

    $html .= '<table>
                    <tr>
                    <td style="width:50%;line-height: 1.0;">' . html_writer::table($gradetable) . '</td>
                    <td style="vertical-align:top;background-color:#C5FFB8;line-height: 1.0;">' . html_writer::table($gradeabbreviationstable) . '</td>
                    </tr>
                </table>';
    // Grade row end
    // Attendance
    $attendancetable = new html_table();
    $attendancetable->head = array();

    $attendancerecords = get_attendance_setup_data($COURSE->id, $user->id);

    foreach ($attendancerecords->data as $grecord) {
        $attendancetable->data[] = [$grecord['session_name'], $grecord['session_value']];
    }

    $html .= '<h4 style="background-color:#F4F7FE;">Attendance' . str_repeat('&nbsp;', 88) . 'Weekly Assignment' . '</h4>';
    // ======================== Homework Table==============

    $homeworktable = new html_table();
    $homeworktable->head = array();

    $homeworkrecords = get_grade_setup_data($COURSE->id, 'Transcript-Homework', $user->id);
    foreach ($homeworkrecords->data as $grecord) {
        $homeworktable->data[] = [$grecord['itemname'], $grecord['grade']];
    }

    $html .= '<table>
                    <tr>
                        <td style="width:60%;line-height: 1.0;">' . html_writer::table($attendancetable) . '</td>
                        <td style="line-height: 1.0;">' . html_writer::table($homeworktable) . '</td>
                    </tr>
                    </table>';

    // Present table

    $presenttable = new html_table('', array('style' => 'width:10% !important;'));
    $presenttable->head = array();
    $presentrecords = [['Present (P)', $attendancerecords->P],
        ['Late (L)', $attendancerecords->L],
        ['Excused (E)', $attendancerecords->E],
        ['Absent (A) ', $attendancerecords->A],
    ];

    foreach ($presentrecords as $grecord) {
        $presenttable->data[] = [$grecord[0], $grecord[1]];
    }

    $html .= html_writer::table($presenttable);
    $otherrecords = get_grade_setup_data($COURSE->id, 'Transcript-others', $user->id);
    if (!empty($otherrecords->data)) {
        $html .= '<h4 style="background-color:#F4F7FE;">Others</h4>';

        $otherstable = new html_table();
        $otherstable->head = array();

        foreach ($otherrecords->data as $grecord) {
            $otherstable->data[] = [$grecord['itemname'], $grecord['grade']];
        }

        $html .= html_writer::table($otherstable);
    }
    $today_date = date("mdY", time());
    $html .= '<h6>' . str_repeat('&nbsp;', 450) . "Created on : " . date("d/F/Y h:i:s A", time()) . '</h6>';

    $doc = new pdf;
    $doc->setPrintHeader(false);
    $doc->setPrintFooter(false);
    $doc->AddPage();
    $doc->writeHTML($html, true, false, false, false, '', '', 'R');

    $shortname = format_string($course->shortname, true, array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));
    if ($download == 2) {
        $doc->Output($CFG->dataroot . '/temp/reports/report_' . $userrecord->email ."_$today_date" . '.pdf', 'F');
        $zip->addFile($CFG->dataroot . '/temp/reports/report_' . $userrecord->email ."_$today_date" . '.pdf', $userrecord->email . "_$today_date"  . '.pdf', 0, 0, true);
    } else if ($download == 1) {
        $downloadfilename = clean_filename($userrecord->email ."_$today_date" . '.pdf');
        $doc->Output($downloadfilename, 'D');
    }
}
if ($download == 2) {
    $zip->close();
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="reports.zip"');
    readfile($filename);
    remove_dir($tempdir, true);
}
?>