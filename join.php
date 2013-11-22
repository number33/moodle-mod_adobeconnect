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
 * @package mod
 * @subpackage adobeconnect
 * @author Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/connect_class.php');
require_once(dirname(__FILE__).'/connect_class_dom.php');

// Course_module ID.
$id       = required_param('id', PARAM_INT);
$groupid  = required_param('groupid', PARAM_INT);
$sesskey  = required_param('sesskey', PARAM_ALPHANUM);


global $CFG, $USER, $DB, $PAGE;

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

// Check if the user's email is the Connect Pro user's login.
$usrobj = new stdClass();
$usrobj = clone($USER);
$usrobj->username = set_username($usrobj->username, $usrobj->email);

$usrcanjoin = false;

$context   = get_context_instance(CONTEXT_MODULE, $cm->id);

// If separate groups is enabled, check if the user is a part of the selected group.
if (NOGROUPS != $cm->groupmode) {

    $usrgroups = groups_get_user_groups($cm->course, $usrobj->id);
    $usrgroups = $usrgroups[0]; // Just want groups and not groupings.

    $group_exists = false !== array_search($groupid, $usrgroups);
    $aag          = has_capability('moodle/site:accessallgroups', $context);

    if ($group_exists || $aag) {
        $usrcanjoin = true;
    }
} else {
    $usrcanjoin = true;
}

// Set page global.
$url = new moodle_url('/mod/adobeconnect/view.php', array('id' => $cm->id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(format_string($adobeconnect->name));
$PAGE->set_heading($course->fullname);

// User has to be in a group.
if ($usrcanjoin and confirm_sesskey($sesskey)) {

    // Get the meeting sco-id.
    $param        = array('instanceid' => $cm->instance, 'groupid' => $groupid);
    $meetingscoid = $DB->get_field('adobeconnect_meeting_groups', 'meetingscoid', $param);

    $aconnect = aconnect_login();

    // Check if the meeting still exists in the shared folder of the Adobe server.
    $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
    $filter       = array('filter-sco-id' => $meetingscoid);
    $meeting      = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

    if (!empty($meeting)) {
        $meeting = current($meeting);
    } else {

        /* Check if the module instance has a user associated with it
           if so, then check the user's adobe connect folder for existince of the meeting */
        if (!empty($adobeconnect->userid)) {
            $username     = get_connect_username($adobeconnect->userid);
            $meetfldscoid = aconnect_get_user_folder_sco_id($aconnect, $username);
            $meeting      = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

            if (!empty($meeting)) {
                $meeting = current($meeting);
            }

        }
    }

    $userrole = false;
    if (empty($meetingscoid) || empty($meeting)) {
        notice(get_string('unableretrdetails', 'adobeconnect'), $url);
    } else {
        // Create user in Adobe Connect with his permission (role) to the meeting.
        if ($userprincipalid = aconnect_create_user($aconnect, $usrobj)) {
            $userrole = aconnect_assign_user_from_moodle_role($aconnect, $userprincipalid, $usrobj->id, $context,
                    $meetingscoid, $adobeconnect->meetingpublic);
        }
    }

    aconnect_logout($aconnect);

    // User is either valid or invalid, if valid redirect user to the meeting url.
    if (!$userrole) {
        notice(get_string('notparticipant', 'adobeconnect'), $url);
    } else {

        $protocol = 'http://';
        $https = false;
        $login = $usrobj->username;

        if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {

            $protocol = 'https://';
            $https = true;
        }

        $aconnect = new connect_class_dom($CFG->adobeconnect_host, $CFG->adobeconnect_port,
                                          '', '', '', $https);

        $aconnect->request_http_header_login(1, $login);

        // Include the port number only if it is a port other than 80.
        $port = '';

        if (!empty($CFG->adobeconnect_port) and (80 != $CFG->adobeconnect_port)) {
            $port = ':' . $CFG->adobeconnect_port;
        }

        add_to_log($course->id, 'adobeconnect', 'join meeting',
                   "join.php?id=$cm->id&groupid=$groupid&sesskey=$sesskey",
                   "Joined $adobeconnect->name meeting", $cm->id);

        redirect($protocol . $CFG->adobeconnect_meethost . $port
                 . $meeting->url
                 . '?session=' . $aconnect->get_cookie());
    }
} else {
    notice(get_string('usergrouprequired', 'adobeconnect'), $url);
}
