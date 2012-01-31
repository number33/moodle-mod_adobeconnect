<?php

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

$id       = required_param('id', PARAM_INT); // course_module ID, or
$groupid  = required_param('groupid', PARAM_INT);
$sesskey  = required_param('sesskey', PARAM_ALPHANUM);


global $CFG, $USER, $DB;

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

// Check if the user's email is the Connect Pro user's login
$usrobj = new stdClass();
$usrobj = clone($USER);
$usrobj->username = set_username($usrobj->username, $usrobj->email);

if (NOGROUPS != $cm->groupmode){

    if (empty($groupid)) {
        $groupid = 0;
        notify(get_string('usergrouprequired', 'adobeconnect'));
        print_footer($course);
        die();

    }

} else {
    $groupid = 0;
}

$usrcanjoin = false;

$usrgroups = groups_get_user_groups($cm->course, $usrobj->id);
$usrgroups = $usrgroups[0]; // Just want groups and not groupings

// If separate groups is enabled, check if the user is a part of the selected group
if (NOGROUPS != $cm->groupmode) {

    $group_exists = false !== array_search($groupid, $usrgroups);
    $context      = get_context_instance(CONTEXT_MODULE, $cm->id);
    $aag          = has_capability('moodle/site:accessallgroups', $context);

    if ($group_exists || $aag) {
        $usrcanjoin = true;
    }
}

// TODO: CHANGE - MODIFY THIS BLOCK SO THAT ONLY USERS WHO ARE A PART OF THE GROUP CAN JOIN
// UPDATE THE CODE, TO LOOK FOR MORE THAN JUST A ROLE WIHIN THE COURSE
// TEST FOR USE CASE: USER WHO IS NOT A PART OF THE GROUP ATTEMPTS TO JOIN
$context = get_context_instance(CONTEXT_COURSE, $cm->course);

// Make sure the user has a role in the course
$crsroles = get_roles_used_in_context($context);

if (empty($crsroles)) {
    $crsroles = array();
}

foreach ($crsroles as $roleid => $crsrole) {
    if (user_has_role_assignment($usrobj->id, $roleid, $context->id)) {
        $usrcanjoin = true;
        print_object($roleid);die();
    }
}
// END OF TODO BLOCK

// user has to be in a group
if ($usrcanjoin and confirm_sesskey($sesskey)) {

    $usrprincipal = 0;
    $validuser    = true;

    // Get the meeting sco-id
    $param        = array('instanceid' => $cm->instance, 'groupid' => $groupid);
    $meetingscoid = $DB->get_field('adobeconnect_meeting_groups', 'meetingscoid', $param);

    $aconnect = aconnect_login();

    // Check if the meeting still exists on the Adobe server
    $meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
    $filter       = array('filter-sco-id' => $meetingscoid);
    $meeting      = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter);

    if (!empty($meeting)) {
        $meeting = current($meeting);
    } else {
        
        /* First check if the module instance has a user associated with it
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

    if (!($usrprincipal = aconnect_user_exists($aconnect, $usrobj))) {
        if (!($usrprincipal = aconnect_create_user($aconnect, $usrobj))) {
            // DEBUG
            print_object("error creating user");
            print_object($aconnect->_xmlresponse);
            $validuser = false;
        }
    }

    $context = get_context_instance(CONTEXT_MODULE, $id);

    // Check the user's capabilities and assign them the Adobe Role
    if (!empty($meetingscoid) and !empty($usrprincipal) and !empty($meeting)) {
        if (has_capability('mod/adobeconnect:meetinghost', $context, $usrobj->id, false)) {
            if (aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, ADOBE_HOST, true)) {
                //DEBUG
//                 echo 'host';
//                 die();
            } else {
                //DEBUG
                print_object('error assign user adobe host role');
                print_object($aconnect->_xmlrequest);
                print_object($aconnect->_xmlresponse);
                $validuser = false;
            }
        } elseif (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id, false)) {
            if (aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, ADOBE_PRESENTER, true)) {
                //DEBUG
//                 echo 'presenter';
//                 die();
            } else {
                //DEBUG
                print_object('error assign user adobe presenter role');
                print_object($aconnect->_xmlrequest);
                print_object($aconnect->_xmlresponse);
                $validuser = false;
            }
        } elseif (has_capability('mod/adobeconnect:meetingparticipant', $context, $usrobj->id, false)) {
            if (aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, ADOBE_PARTICIPANT, true)) {
                //DEBUG
//                 echo 'participant';
//                 die();
            } else {
                //DEBUG
                print_object('error assign user adobe particpant role');
                print_object($aconnect->_xmlrequest);
                print_object($aconnect->_xmlresponse);
                $validuser = false;
            }
        } else {
            // Check if meeting is public and allow them to join
            if ($adobeconnect->meetingpublic) {
                // if for a public meeting the user does not not have either of presenter or participant capabilities then give
                // the user the participant role for the meeting
                aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, ADOBE_PARTICIPANT, true);
                $validuser = true;
            } else {
                $validuser = false;
            }
        }
    } else {
        $validuser = false;
        notice(get_string('unableretrdetails', 'adobeconnect'));
    }

    aconnect_logout($aconnect);

    // User is either valid or invalid, if valid redirect user to the meeting url
    if (empty($validuser)) {
        notice(get_string('notparticipant', 'adobeconnect'));
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

        // Include the port number only if it is a port other than 80
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
    notice(get_string('usernotenrolled', 'adobeconnect'));
}
