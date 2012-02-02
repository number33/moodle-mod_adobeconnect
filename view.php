<?php  // $Id: view.php,v 1.1.2.13 2011/05/09 21:41:28 adelamarre Exp $

/**
 * This page prints a particular instance of adobeconnect
 *
 * @author  Your Name <adelamarre@remote-learner.net>
 * @version $Id: view.php,v 1.1.2.13 2011/05/09 21:41:28 adelamarre Exp $
 * @package mod/adobeconnect
 */

/// (Replace adobeconnect with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/connect_class.php');
require_once(dirname(__FILE__).'/connect_class_dom.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // adobeconnect instance ID
$groupid = optional_param('group', 0, PARAM_INT);

if ($id) {
    if (! $cm = get_coursemodule_from_id('adobeconnect', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $adobeconnect = get_record('adobeconnect', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($a) {
    if (! $adobeconnect = get_record('adobeconnect', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $adobeconnect->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('adobeconnect', $adobeconnect->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

global $CFG, $USER;

// Check if the user's email is the Connect Pro user's login
$usrobj = new stdClass();
$usrobj = clone($USER);

$usrobj->username = set_username($usrobj->username, $usrobj->email);

/// Print the page header
$stradobeconnects = get_string('modulenameplural', 'adobeconnect');
$stradobeconnect  = get_string('modulename', 'adobeconnect');

$navlinks = array();
$navlinks[] = array('name' => $stradobeconnects, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($adobeconnect->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($adobeconnect->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $stradobeconnect), navmenu($course, $cm));

$context     = get_context_instance(CONTEXT_MODULE, $id);
$user_groups = array();

// The batch of code below handles the display of Moodle groups
if ($cm->groupmode) {

    $url = "{$CFG->wwwroot}/mod/adobeconnect/view.php?id=$id";

    // Retrieve a list of groups that the current user can see/manage
    $user_groups = groups_get_activity_allowed_groups($cm, $USER->id);

    if ($user_groups) {

        // Print groups selector drop down
        groups_print_activity_menu($cm, $url, false, true);


        // Retrieve the currently active group for the user's session
        $groupid = groups_get_activity_group($cm);

        /* Depending on the series of events groups_get_activity_group will 
         * return a groupid value of  0 even if the user belongs to a group.
         * If the groupid is set to 0 then use the first group that the user
         * belongs to.
         */
        $aag = has_capability('moodle/site:accessallgroups', $context);
        
        if (0 == $groupid) {
            $groups = groups_get_user_groups($cm->course, $USER->id);
            $groups = current($groups);

            if (!empty($groups)) {

                $groupid = key($SESSION->activegroup[$cm->course]);
            } elseif ($aag) {
                /* If the user does not explicitely belong to any group
                 * check their capabilities to see if they have access
                 * to manage all groups; and if so display the first course
                 * group by default
                 */
                $groupid = key($user_groups);
            }
        }
    }
}

/// Print the main part of the page
$usrgroups = groups_get_user_groups($cm->course, $usrobj->id);
$usrgroups = $usrgroups[0]; // Just want groups and not groupings

$sql = "SELECT meetingscoid FROM {$CFG->prefix}adobeconnect_meeting_groups amg WHERE ".
       "amg.instanceid = {$cm->instance}";


$meetscoids = get_records_sql($sql);
$recording = array();

if (!empty($meetscoids)) {
    $recscoids = array();

    $aconnect = aconnect_login();

    // Get the forced recordings folder sco-id
    // Get recordings that are based off of the meeting
    $fldid = aconnect_get_folder($aconnect, 'forced-archives');
    foreach($meetscoids as $scoid) {

        $data = aconnect_get_recordings($aconnect, $fldid, $scoid->meetingscoid);

        if (!empty($data)) {
          // Store recordings in an array to be moved to the Adobe shared folder later on
          $recscoids = array_merge($recscoids, array_keys($data));

        }

    }

    // Move the meetings to the shared content folder
    if (!empty($recscoids)) {
        $recscoids = array_flip($recscoids);

        if (aconnect_move_to_shared($aconnect, $recscoids)) {
            // do nothing
        }
    }

    //Get the shared content folder sco-id
    // Create a list of recordings moved to the shared content folder
    $fldid = aconnect_get_folder($aconnect, 'content');
    foreach($meetscoids as $scoid) {

        // May need this later on
        $data = aconnect_get_recordings($aconnect, $fldid, $scoid->meetingscoid);

        if (!empty($data)) {
            $recording[] = $data;
        }

        $data2 = aconnect_get_recordings($aconnect, $scoid->meetingscoid, $scoid->meetingscoid);

        if (!empty($data2)) {
             $recording[] = $data2;
        }
    }

    // Clean up any duplciated meeting recordings.  Duplicated meeting recordings happen when a the
    // recording settings on ACP server change between publishing the recording links in meeting folders and
    // not publishing the recording links in meeting folders
    $names = array();
    foreach ($recording as $key => $recordingarray) {

        foreach ($recordingarray as $key2 => $record) {


            if (!empty($names)) {

                if (!array_search($record->name, $names)) {

                    $names[] = $record->name;
                } else {

                    unset($recording[$key][$key2]);
                }
            } else {

                $names[] = $record->name;
            }
        }
    }
    
    unset($names);

    // Check if the user exists and if not create the new user
    if (!($usrprincipal = aconnect_user_exists($aconnect, $usrobj))) {
        if (!($usrprincipal = aconnect_create_user($aconnect, $usrobj))) {
            // DEBUG
            debugging("error creating user", DEBUG_DEVELOPER);

//            print_object("error creating user");
//            print_object($aconnect->_xmlresponse);
            $validuser = false;
        }
    }

    // Check the user's capability and assign them view permissions to the recordings folder
    // if it's a public meeting give them permissions regardless
    if ($cm->groupmode) {

        if (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id) or
            has_capability('mod/adobeconnect:meetingparticipant', $context, $usrobj->id)) {
            if (aconnect_assign_user_perm($aconnect, $usrprincipal, $fldid, ADOBE_VIEW_ROLE)) {
                //DEBUG
                // echo 'true';
            } else {
                //DEBUG
                debugging("error assign user recording folder permissions", DEBUG_DEVELOPER);
//                print_object('error assign user recording folder permissions');
//                print_object($aconnect->_xmlrequest);
//                print_object($aconnect->_xmlresponse);
            }
        }
    } else {
        aconnect_assign_user_perm($aconnect, $usrprincipal, $fldid, ADOBE_VIEW_ROLE);
    }

    aconnect_logout($aconnect);
}

// Log in the current user
$login = $usrobj->username;
//$password  = $usrobj->username;
$https = false;

if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {
    $https = true;
}


$aconnect = new connect_class_dom($CFG->adobeconnect_host, $CFG->adobeconnect_port,
                                  '', '', '', $https);
$aconnect->request_http_header_login(1, $login);
$adobesession = $aconnect->get_cookie();

if (($formdata = data_submitted($CFG->wwwroot . '/mod/adobeconnect/view.php')) && confirm_sesskey()) {

    // Edit participants
    if (isset($formdata->participants)) {
        $context = get_context_instance(CONTEXT_MODULE, $id);
        // Using course context because we want the assign page to use that context
        // Otherwise the user would have to re-assign users for every activity instance
        //$context = get_context_instance(CONTEXT_COURSE, $course->id);

        $roleid = get_field('role', 'id', 'shortname', 'adobeconnectpresenter');

        if (!empty($roleid)) {
            redirect("assign.php?id=$id&amp;contextid={$context->id}&amp;roleid=$roleid&amp;groupid={$formdata->group}", '', 0);
        } else {
            notice(get_string('nopresenterrole', 'adobeconnect'));
        }
    }

    // Edit participants
    if (isset($formdata->viewcontent)) {
        redirect("viewcontent.php?id=$id&amp;groupid=$groupid", '', 0);
    }
}

$aconnect = aconnect_login();

// Get the Meeting details
$scoid        = get_field('adobeconnect_meeting_groups', 'meetingscoid',
                          'instanceid', $adobeconnect->id,
                          'groupid', $groupid);
$meetfldscoid = aconnect_get_folder($aconnect, 'meetings');
$filter       = array('filter-sco-id' => $scoid);

if (($meeting = aconnect_meeting_exists($aconnect, $meetfldscoid, $filter))) {
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

    // If meeting does not exist then display an error message
    if (empty($meeting)) {
        notice(get_string('nomeeting', 'adobeconnect'), '', $course);
        aconnect_logout($aconnect);
        die();
    }

}


aconnect_logout($aconnect);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$sesskey = !empty($usrobj->sesskey) ? $usrobj->sesskey : '';

echo '<br /><br />';
echo '<form name="meetingdetail" action="' . $CFG->wwwroot . '/mod/adobeconnect/view.php" method="post">' . "\n";

// print meeting detail field set
echo '<div id="aconfldset1" class="aconfldset">'."\n";
echo '<fieldset>'."\n";

echo '<legend>'.get_string('meetinginfo', 'adobeconnect').'</legend>'."\n";

echo '<div class="aconmeetinforow">'."\n";

echo '<div class="aconlabeltitle" id="aconmeetnametitle">'."\n";
echo '<label for="lblmeetingnametitle">'.get_string('meetingname', 'adobeconnect').':</label>'."\n";
echo '</div>'."\n";

$name = html_entity_decode($meeting->name);
echo '<div class="aconlabeltext" id="aconmeetnametxt">'."\n";
echo '<label for="lblmeetingname">'.format_string($name).'</label><br />'."\n";
echo '</div>'."\n";

echo '</div>'."\n";

if (has_capability('mod/adobeconnect:meetingpresenter', $context) or
    has_capability('mod/adobeconnect:meetinghost', $context)) {

    // Get HTTPS setting
    $https      = false;
    $protocol   = 'http://';
    if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {
        $https      = true;
        $protocol   = 'https://';
    }

    // Include the port number only if it is a port other than 80
    $port = '';

    if (!empty($CFG->adobeconnect_port) and (80 != $CFG->adobeconnect_port)) {
        $port = ':' . $CFG->adobeconnect_port;
    }

    $url = $protocol . $CFG->adobeconnect_meethost . $port
           . $meeting->url;
    echo '<div class="aconmeetinforow">'."\n";

    echo '<div class="aconlabeltitle" id="aconmeeturltitle">'."\n";
    echo '<label for="lblmeetingurltitle">'.get_string('meeturl', 'adobeconnect').':</label>'."\n";
    echo '</div>'."\n";

    echo '<div class="aconlabeltext" id="aconmeeturltext">'."\n";
    echo '<label for="lblmeetingurl">'.$url.'</label><br />'."\n";
    echo '</div>'."\n";

    echo '</div>'."\n";

    echo '<div class="aconmeetinforow">'."\n";

    echo '<div class="aconlabeltitle" id="aconmeetinfotitle">'."\n";
    echo '<label for="lblmeetinginfotitle">'.get_string('meetinfo', 'adobeconnect').':</label>'."\n";
    echo '</div>'."\n";

    $url = $protocol.$CFG->adobeconnect_meethost.$port.'/admin/meeting/sco/info?principal-id='.
           $usrprincipal.'&amp;sco-id='.$scoid.'&amp;session='.$adobesession;
    echo '<div class="aconlabeltext" id="aconmeetinfotext">'."\n";
    echo '<a href="'. $url . '" target="_blank">'. get_string('meetinfotxt', 'adobeconnect') . '</a><br />'."\n";
    echo '</div>'."\n";

    echo '</div>'."\n";

//    echo '<div class="aconbtninfo">'."\n";
//    echo button_to_popup_window('www.google.ca',
//                                'btnname', get_string('joinmeeting', 'adobeconnect'), 900, 900, null, null, true);
//    echo '</div>'."\n";

//$port = '';
//    echo '<div class="aconbtninfo">'."\n";
//    $infourl= $protocol.$CFG->adobeconnect_meethost.$port.'/admin/meeting/sco/info?principal-id='.
//            $usrprincipal.'&amp;sco-id='.$scoid.'&amp;session='.$adobesession;
//    echo '<input type="button" name="info" value="'.get_string('meetinginfo', 'adobeconnect').
//        '" onClick="window.open(\''.$infourl.'\',\'meetinginfo\',\'dependent=no\'); return false;">';
//    echo '</div>'."\n";



//---------


}

echo '<div class="aconmeetinforow">'."\n";

echo '<div class="aconlabeltitle" id="aconmeetstarttitle">'."\n";
echo '<label for="lblmeetingstarttitle">'.get_string('meetingstart', 'adobeconnect').':</label>'."\n";
echo '</div>'."\n";

//  CONTRIB-2929 - remove date format and let Moodle decide the format
$time = userdate($adobeconnect->starttime);
echo '<div class="aconlabeltext" id="aconmeetstarttxt">'."\n";
echo '<label for="lblmeetingstart">'.$time.'</label><br />'."\n";
echo '</div>'."\n";

echo '</div>'."\n";

echo '<div class="aconmeetinforow">'."\n";

echo '<div class="aconlabeltitle" id="aconmeetendtitle">'."\n";
echo '<label for="lblmeetingendtitle">'.get_string('meetingend', 'adobeconnect').':</label>'."\n";
echo '</div>'."\n";

$time = userdate($adobeconnect->endtime);
echo '<div class="aconlabeltext" id="aconmeetendtxt">'."\n";
echo '<label for="lblmeetingend">'.$time.'</label><br />'."\n";
echo '</div>'."\n";

echo '</div>'."\n";

echo '<div class="aconmeetinforow">'."\n";

echo '<div class="aconlabeltitle" id="aconmeetsummarytitle">'."\n";
echo '<label for="lblmeetingsummarytitle">'.get_string('meetingintro', 'adobeconnect').':</label>'."\n";
echo '</div>'."\n";

echo '<div class="aconlabeltext" id="aconmeetsummarytxt">'."\n";
echo '<label for="lblmeetingsummary">'. format_string($adobeconnect->intro) .'</label><br />'."\n";
echo '</div>'."\n";

echo '</div>'."\n";

echo '</fieldset>'."\n";
echo '</div>'."\n";

echo '<br />';

echo '<div class="aconbtnrow">'."\n";

echo '<div class="aconbtnjoin">'."\n";
echo button_to_popup_window('/mod/adobeconnect/join.php?id='.$id.'&amp;sesskey='.$sesskey.'&amp;groupid='.$groupid,
                            'btnname', get_string('joinmeeting', 'adobeconnect'), 900, 900, null, null, true);
echo '</div>'."\n";

if (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id) or
    has_capability('mod/adobeconnect:meetinghost', $context, $usrobj->id)){
    echo '<div class="aconbtnroles">'."\n";
    echo '<input type="submit" name="participants" value="'.get_string('selectparticipants', 'adobeconnect').'">';
    echo '</div>'."\n";

}

echo '</div>'."\n";

echo '<input type="hidden" name="id" value="'.$id.'">'."\n";
echo '<input type="hidden" name="group" value="'.$groupid.'">'."\n";
echo '<input type="hidden" name="sesskey" value="'.$sesskey.'">'."\n";

echo '</form>'."\n";

echo '<br />';


$showrecordings = false;
// Check if meeting is private, if so check the user's capability.  If public show recorded meetings
if (!$adobeconnect->meetingpublic) {
    if (has_capability('mod/adobeconnect:meetingpresenter', $context, $usrobj->id) or
        has_capability('mod/adobeconnect:meetingparticipant', $context, $usrobj->id)) {
            $showrecordings = true;
    }
} else {
    $showrecordings = true;
}

$recordings = $recording;

if ($showrecordings and !empty($recordings)) {

    $recording_fieldset = '<div id="aconfldset2" class="aconfldset">'."\n".
                          '<fieldset>'."\n".
                          '<legend>'.get_string('recordinghdr', 'adobeconnect').'</legend>'."\n".
                          '<div class="aconrecording">'."\n";
    $recording_links = '';
    
    foreach ($recordings as $key => $recordinggrp) {

        if (!empty($recordinggrp)) {

            foreach($recordinggrp as $recording_scoid => $recording) {
                
                // Check if the recording source matches the currently displayed meeting
                if ($recording->sourcesco != $scoid) {
                    continue;
                }

                $name = html_entity_decode($recording->name);
                $recording_links .= '<div class="aconrecordingrow">'."\n".
                                    '<a href="joinrecording.php?id=' . $id. '&recording='. $recording_scoid .
                                    '&groupid=' . $groupid . '&sesskey=' . $USER->sesskey .
                                    '" target="_blank">'. format_string($name) .'</a><br />'.
                                    '</div>'."\n";
            }
        }
    }

    if (!empty($recording_links)) {
        $recording_fieldset .= $recording_links;
        $recording_fieldset .= '</div>'."\n".
                               '</fieldset>'."\n".
                               '</div>'."\n";
        echo $recording_fieldset;
    }
}

add_to_log($course->id, 'adobeconnect', 'view',
           "view.php?id=$cm->id", "View {$adobeconnect->name} details", $cm->id);

/// Finish the page
print_footer($course);

?>