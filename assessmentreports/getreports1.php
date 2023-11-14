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
$userid    = optional_param('userid', 0, PARAM_INT);
$role     = optional_param('role', 0, PARAM_INT);
$download     = optional_param('download', 0, PARAM_INT);
$autosize = report_assessmentreports_resolve_auto_size();
$size     = optional_param('size', $autosize, PARAM_INT);
$course   = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

// Setup page.
$PAGE->set_url('/report/assessmentreports/getreports.php', array('id' => $id, 'userid'=>$userid));
if ($mode === assessmentreports_MODE_PRINT) {
    $PAGE->set_pagelayout('print');
} else {
    $PAGE->set_pagelayout('report');
}
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/assessmentreports:view', $coursecontext);

if($download == 2) {
    $users = get_enrolled_users(
        $coursecontext,
        '',
    );
} else if($download == 1) {
    
    $user = new stdClass();
    $user->id = $userid;
    $users[] = $user;
}

global $CFG;
// Zip file initialisation
    $zip = new ZipArchive();
    $filename = $CFG->dataroot.'/temp/reports.zip';
    $zip->open($filename, ZipArchive::CREATE);

    foreach ($users as $user) {
        $userrecord = $DB->get_record('user', array('id'=>$user->id));
        $html = '';

        $html .= html_writer::start_tag('div', array('class'=>'row'));
        $html .= html_writer::start_tag('div', array('class'=>'col-md-12'));
        $html .= html_writer::tag('img', '', array('src'=>$CFG->wwwroot.'/report/assessmentreports/pix/headerimage.jpeg','class'=>'headimg',));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');

        $html .= '<h4 style="background-color:#F4F7FE;padding:7px;margin:5px;">Student Details</h4>';
        
        $table = new html_table();
        $table->head = array();
        $records=[['First Name', $userrecord->firstname],
        ['Email',$userrecord->email],
        ['Last Name', $userrecord->lastname],
        ['Teacher', ''],
        ['Year / Branch']];
        for ($i=0;$i<=count($records);++$i) {        
            $table->data[] = [$records[$i][0], $records[$i][1], $records[$i+1][0], $records[$i+1][1]];
            $i++;
        }

        $html .= '<div class="row">';
        $html .= '<div class="col-md-12">';
        $html .= html_writer::table($table);
        $html .= '</div>';
        $html .= '</div>';

        // ============================Grade table=====================//
        $html .= '<div class="grade-wrapper" style="width:100%;">';
        $html .= '<h4 style="background-color:#F4F7FE;padding:7px;margin:5px;">Grade</h4>';

        $gradetable = new html_table('', array('style'=> ''));
        //$gradetable->head = array();
        $graderecords=[['Unit 3 Final Assessment Listening (Real)', 'EE'],
        ['Unit 3 Final Assessment Listening (Real)', 'EE'],
        ['Unit 3 Final Assessment Listening (Real)', 'EE'],
        ['Unit 3 Final Assessment Listening (Real)', 'EE'],
        ['Unit 3 Final Assessment Listening (Real)', 'EE'],];

        foreach($graderecords as $grecord) {
            $gradetable->data[] = [$grecord[0], $grecord[1]];
        }

        //Grade abbreviations table

        $gradeabbreviationstable = new html_table('gradeabbreviationstable', array('style'=>'background-color:#C5FFB8;'));
        $gradeabbrevationrecords=[
        ['EE', 'Exceed Expectations'],
        ['ME-M ME-S', 'Meeting Expectations-Strong'],
        ['EE ME-M	', 'Meeting Expectations-Minimal'],
        ['ME-S D', 'Does not meet expectations']];

        foreach($gradeabbrevationrecords as $grecord) {
            $gradeabbreviationstable->data[] = [$grecord[0], $grecord[1]];
        }


        // Grade abbreviations table end

        $html .= '<table>
                    <tr>
                    <td style="width:50%">'.html_writer::table($gradetable).'</td>
                    <td style="vertical-align:top;">'.html_writer::table($gradeabbreviationstable).'</td>
                    </tr>
                </table>';
        $html .= '</div>';
        // Grade row end

        // Attendance
        $attendancetable = new html_table();
        $attendancetable->head = array();
        $attendancerecords=[['4 Mar 2023 9.45AM', 'P(2/2)'],
        ['4 Mar 2023 9.45AM', 'P(2/2)'],
        ['4 Mar 2023 9.45AM', 'P(2/2)'],];

        foreach($attendancerecords as $grecord) {
            $attendancetable->data[] = [$grecord[0], $grecord[1]];
        }

        $html .= '<h4 style="background-color:#F4F7FE;padding:7px;margin:5px;">Attendance</h4>';
        $html .= html_writer::start_tag('div', array('class'=>'', 'style'=> 'width:100%;' ));
        // ======================== Homework Table==============

        $homeworktable = new html_table();
        $homeworktable->head = array();
        $homeworkrecords=[['Lesson 1 Assignment (Real) : Completed'],
        ['Lesson 2 Assignment (Real) : Completed'],
        ['Lesson 3 Assignment (Real) : Completed'],
        ['Lesson 4 Assignment (Real) : Completed']];

        foreach($homeworkrecords as $grecord) {
            $homeworktable->data[] = [$grecord[0], $grecord[1]];
        }
        $html .= '<table>
                    <tr>
                        <td style="width:70%;">'.html_writer::table($attendancetable).'</td>
                        <td style="">'.html_writer::table($homeworktable).'</td>
                    </tr>
                    </table>';

        // Present table

        $presenttable = new html_table('', array('style'=>'width:60% !important;'));
        $presenttable->head = array();
        $presentrecords=[['Present (P)' , '7'],
        ['Leave (L)'  , '0'],
        ['Excused (E)' , '0'],
        ['Absent (A) ' , '1'],
        ['Taken sessions', '8'],
        ['Points' , '14/16'],
        ['Percentage' , '87.5'],
        ];

        foreach($presentrecords as $grecord) {
            $presenttable->data[] = [$grecord[0], $grecord[1]];
        }


        $html .= html_writer::start_tag('div', array('class'=>'','style'=> 'width:50%;'));

        $html .=  html_writer::table($presenttable);
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');



        $html .= '<div style="width:100%">';
        $html .= '<h4 style="background-color:#F4F7FE;padding:7px;margin:5px;">Others</h4>';

        $otherstable = new html_table();
        $otherstable->head = array();
        $otherrecords=[['WMLD Project', 'Completed']];

        foreach($otherrecords as $grecord) {
            $otherstable->data[] = [$grecord[0], $grecord[1]];
        }
        $html .=  html_writer::table($otherstable);
        $html .= '</div>';

        $doc = new pdf;
        $doc->setPrintHeader(false);
        $doc->setPrintFooter(false);
        $doc->AddPage();
        $doc->writeHTML($html, true, false, false, false, '', '', 'R');

        $shortname = format_string($course->shortname, true, array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));
        if($download == 2) {
        $doc->Output($CFG->dataroot.'/temp/report_' . $userrecord->firstname .'_'.$userrecord->id. '.pdf', 'F');
        $zip->addFile($CFG->dataroot.'/temp/report_' . $userrecord->firstname .'_'.$userrecord->id. '.pdf', 'report_' . $userrecord->firstname .'_'.$userrecord->id. '.pdf', 0, 0, true);
        } else if($download == 1) {
            $downloadfilename = clean_filename($userrecord->firstname .'_'. $userrecord->id .'.pdf');
            $doc->Output($downloadfilename,'D');
        }

    }
    if($download == 2) {
        $zip->close();
        header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="certificates.zip"');
        readfile($filename);
    }

?>
<style>
    #gradeabbreviationstable{background-color: #C5FFB8;}
</style>