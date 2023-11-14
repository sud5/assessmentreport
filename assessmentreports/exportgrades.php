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

require_once '../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once $CFG->dirroot.'/grade/export/xls/grade_export_xls.php';



$id                = required_param('id', PARAM_INT); // course id
$PAGE->set_url('/report/assessmentreports/exportgrades.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    throw new \moodle_exception('invalidcourseid');
}
global $COURSE;
require_login($course);
$context = context_course::instance($id);
$groupid = groups_get_course_group($course, true);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/xls:view', $context);
// We need to call this method here before any print otherwise the menu won't display.
// If you use this method without this check, will break the direct grade exporting (without publishing).
$key = optional_param('key', '', PARAM_RAW);
if (!empty($CFG->gradepublishing) && !empty($key)) {
    $actionbar = new \core_grades\output\export_publish_action_bar($context, 'xls');
    print_grade_page_head($COURSE->id, 'export', 'xls',
        get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_xls'),
        false, false, true, null, null, null, $actionbar);
}

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        throw new \moodle_exception('cannotaccessgroup', 'grades');
    }
}
         $switch = grade_get_setting($COURSE->id, 'aggregationposition', $CFG->grade_aggregationposition);
        // Grab the grade_seq for this course
        $gseq = new grade_seq($COURSE->id, $switch);
         $itemids = [];
        if ($grade_items = $gseq->items) {
              foreach ($grade_items as $grade_item) {
                // Is the grade_item hidden? If so, can the user see hidden grade_items?
                if ($grade_item->is_hidden() && !$canviewhidden) {
                    continue;
              }
                 $itemids[$grade_item->id] = 1;
                }
        }
 $formdata = new stdClass(); 
 $formdata->mform_isexpanded_id_gradeitems = 1; 
 $formdata->itemids = $itemids; 
 $formdata->display = array('real' => 1, 'percentage' => 2, 'letter' => 0); 
 $formdata->decimals = 2; 
 $formdata->id = $id; 
 $formdata->submitbutton = 'Download'; 
 $formdata->checkbox_controller1 = 1; 
 $formdata->mform_isexpanded_id_options = 1; 
 $formdata->export_feedback = 0; 
 $formdata->export_onlyactive = 1; 
$export = new grade_export_xls($course, $groupid, $formdata);

// If the gradepublishing is enabled and user key is selected print the grade publishing link.
if (!empty($CFG->gradepublishing) && !empty($key)) {
    groups_print_course_menu($course, 'index.php?id='.$id);
    echo $export->get_grade_publishing_url();
    echo $OUTPUT->footer();
} else {
    $event = \gradeexport_xls\event\grade_exported::create(array('context' => $context));
    $event->trigger();
    $export->print_grades();
}
