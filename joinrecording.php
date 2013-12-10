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
 * The purpose of this file is to add a log entry when the user views a
 * recording
 *
 * @author  Your Name <adelamarre@remote-learner.net>
 * @version $Id: view.php,v 1.1.2.13 2011/05/09 21:41:28 adelamarre Exp $
 * @package mod/adobeconnect
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/connect_class.php');
require_once(dirname(__FILE__).'/connect_class_dom.php');

$id         = required_param('id', PARAM_INT);
$groupid    = required_param('groupid', PARAM_INT);
$recscoid   = required_param('recording', PARAM_INT);

global $CFG, $USER, $DB, $PAGE;

// Do the usual Moodle setup.
if (! $cm = get_coursemodule_from_id('adobeconnect', $id)) {
    error('Course Module ID was incorrect');
}
$cond = array('id' => $cm->course);
if (! $course = $DB->get_record('course', $cond)) {
    error('Course is misconfigured');
}

$cond = array('id' => $cm->instance);
if (! $adobeconnect = $DB->get_record('adobeconnect', $cond)) {
    error('Course module is incorrect');
}

require_login($course, true, $cm);

// Get HTTPS setting.
$https      = false;
$protocol   = 'http://';
if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {
    $https      = true;
    $protocol   = 'https://';
}

// Create a Connect Pro login session for this user.
$usrobj = new stdClass();
$usrobj = clone($USER);
$login  = $usrobj->username = set_username($usrobj->username, $usrobj->email);

$params = array('instanceid' => $cm->instance, 'groupid' => $groupid);
$sql = "SELECT meetingscoid FROM {adobeconnect_meeting_groups} amg WHERE ".
       "amg.instanceid = :instanceid AND amg.groupid = :groupid";

$meetscoid = $DB->get_record_sql($sql, $params);

// Get the Meeting recording details.
$aconnect   = aconnect_login();
$recording  = array();
$fldid      = aconnect_get_folder($aconnect, 'content');
$usrcanjoin = false;
$context    = get_context_instance(CONTEXT_MODULE, $cm->id);
$data       = aconnect_get_recordings($aconnect, $fldid, $meetscoid->meetingscoid);

// Set page global.
$url = new moodle_url('/mod/adobeconnect/view.php', array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($adobeconnect->name));
$PAGE->set_heading($course->fullname);

if (!empty($data) && array_key_exists($recscoid, $data)) {

    $recording = $data[$recscoid];
} else {

    // If at first you don't succeed ...
    $data2 = aconnect_get_recordings($aconnect, $meetscoid->meetingscoid, $meetscoid->meetingscoid);

    if (!empty($data2) && array_key_exists($recscoid, $data2)) {
        $recording = $data2[$recscoid];
    }
}

if (empty($recording) and confirm_sesskey()) {
    notify(get_string('errormeeting', 'adobeconnect'));
    die();
}

// If separate groups is enabled, check if the user is a part of the selected group.
if (NOGROUPS != $cm->groupmode) {
    $usrgroups = groups_get_user_groups($cm->course, $USER->id);
    $usrgroups = $usrgroups[0]; // Just want groups and not groupings.

    $groupexists = false !== array_search($groupid, $usrgroups);
    $aag          = has_capability('moodle/site:accessallgroups', $context);

    if ($groupexists || $aag) {
        $usrcanjoin = true;
    }
} else {
    $usrcanjoin = true;
}

if (!$usrcanjoin) {
    notice(get_string('usergrouprequired', 'adobeconnect'), $url);
} else {
    if (empty($meetscoid->meetingscoid)) {
        notice(get_string('unableretrdetails', 'adobeconnect'), $url);
    } else {
        // Create user in Adobe Connect with his permission (role) to the meeting.
        if ($userprincipalid = aconnect_create_user($aconnect, $usrobj)) {
            $userrole = aconnect_assign_user_from_moodle_role($aconnect, $userprincipalid, $usrobj->id, $context,
                    $meetscoid->meetingscoid, $adobeconnect->meetingpublic);
        }
    }
}

aconnect_logout($aconnect);

add_to_log($course->id, 'adobeconnect', 'view',
           "view.php?id=$cm->id", "View recording {$adobeconnect->name} details", $cm->id);

// Include the port number only if it is a port other than 80.
$port = '';

if (!empty($CFG->adobeconnect_port) and (80 != $CFG->adobeconnect_port)) {
    $port = ':' . $CFG->adobeconnect_port;
}

$aconnect = new connect_class_dom($CFG->adobeconnect_host, $CFG->adobeconnect_port,
                                  '', '', '', $https);

$aconnect->request_http_header_login(1, $login);
$adobesession = $aconnect->get_cookie();

redirect($protocol . $CFG->adobeconnect_meethost . $port
        . $recording->url . '?session=' . $aconnect->get_cookie());
