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
 * Signup event handlers
 *
 * @package    enrol_signup
 * @copyright  2011 Qontori Pte Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/moodlelib.php');


global $CFG, $DB, $SESSION;
//$username = $_POST['username'];

$id = optional_param('id', '', PARAM_RAW);
//$courseid = optional_param('courseid', '', PARAM_INT);
//$token = optional_param('token', '', PARAM_RAW);

//if($token == 'js6nngh' && !empty($username)){
$user = $DB->get_record('user', array('id' => $id));
complete_user_login($user);
//if($courseid){
//  redirect($CFG->wwwroot."/course/view.php?id=$courseid");
//} else{
  redirect($CFG->wwwroot."/my");
//}
//}else{
//  redirect($CFG->wwwroot."/login/index.php");
//}



//require_once($CFG->libdir . '/moodlelib.php');
//$user = $DB->get_record('user', array('username' => 'admin'));
//complete_user_login($user);